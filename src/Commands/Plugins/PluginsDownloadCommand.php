<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\ProcessManager;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Utilities\HasStats;
use AspirePress\AspireSync\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PluginsDownloadCommand extends AbstractBaseCommand
{
    use HasStats;

    public function __construct(
        private PluginListService $pluginListService,
        private PluginMetadataService $pluginMetadataService,
        private StatsMetadataService $statsMetadataService,
        private ProcessManager $processManager,
    ) {
        parent::__construct();
        $this->processManager->setProcessStartDelay(200); /* milliseconds */
        $this->processManager->setProcessStartCallback($this->onDownloadProcessStarted(...));
        $this->processManager->setProcessFinishCallback($this->onDownloadProcessFinished(...));
    }

    protected function configure(): void
    {
        $this->setName('plugins:download')
            ->setDescription('Grabs plugins (with number of specified versions or explicitly specified plugins) from the origin repo')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption('plugins', null, InputOption::VALUE_OPTIONAL, 'List of plugins to request')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force download even if file exists')
            ->addOption('download-all', null, InputOption::VALUE_NONE, 'Download all plugins');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always("Running command {$this->getName()}");
        $this->startTimer();
        $numVersions = $input->getArgument('num-versions');
        $pluginList  = $input->getOption('plugins');

        if ($pluginList) {
            $pluginList = explode(',', $pluginList);
            foreach ($pluginList as $k => $plugin) {
                $pluginList[$k] = trim($plugin);
            }
        }

        $this->debug('Getting list of plugins...');

        if ($input->getOption('download-all')) {
            $pluginsToUpdate = $this->pluginListService->getUpdatedListOfItems($pluginList, 'default');
        } else {
            $pluginsToUpdate = $this->pluginListService->getUpdatedListOfItems($pluginList);
        }

        $this->debug(count($pluginsToUpdate) . ' plugins to download...');
        if (count($pluginsToUpdate) === 0) {
            $this->success('No plugins to download...exiting...');
            return Command::SUCCESS;
        }

        $flags = [];
        $input->getOption('force') and $flags[] = '--force';
        $commands = [];
        foreach ($pluginsToUpdate as $plugin => $versions) {
            $versions = $this->determineVersionsToDownload($plugin, $versions, $numVersions);
            foreach ($versions as $version) {
                [$version, $message] = VersionUtil::cleanVersion($version);
                if (!$version) {
                    $this->notice("Skipping $plugin: $message");
                    continue;
                }
                $commands[] = ['aspiresync', 'plugins:download:single', $plugin, $version, ...$flags];
            }
        }

        foreach ($commands as $command) {
            $this->debug("QUEUE: " . implode(' ', $command));
            $this->processManager->addProcess(new Process($command));
        }

        $this->info("Total downloads queued: " . count($commands));
        $this->processManager->waitForAllProcesses();

        $this->endTimer();
        $this->always($this->getRunInfo($this->getCalculatedStats()));
        $this->statsMetadataService->logStats($this->getName(), $this->stats);
        return Command::SUCCESS;
    }

    private function onDownloadProcessStarted(Process $process): void
    {
        $this->debug("START: " . str_replace("'", "", $process->getCommandLine()));
    }

    private function onDownloadProcessFinished(Process $process): void
    {
        echo $process->getOutput();
    }

    /**
     * @param string[] $versions
     * @return array<int, string>
     */
    private function determineVersionsToDownload(string $plugin, array $versions, string $numToDownload): array
    {
        $download = match ($numToDownload) {
            'all' => $versions,
            'latest' => [VersionUtil::getLatestVersion($versions)],
            default => VersionUtil::limitVersions(VersionUtil::sortVersions($versions), (int) $numToDownload),
        };
        return $this->pluginMetadataService->getUnprocessedVersions($plugin, $download);
    }
}
