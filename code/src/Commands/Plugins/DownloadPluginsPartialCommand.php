<?php

declare(strict_types=1);

namespace AssetGrabber\Commands\Plugins;

use AssetGrabber\Commands\AbstractBaseCommand;
use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Utilities\GetItemsFromSourceTrait;
use AssetGrabber\Utilities\ProcessWaitUtil;
use AssetGrabber\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DownloadPluginsPartialCommand extends AbstractBaseCommand
{
    use GetItemsFromSourceTrait;

    public function __construct(private PluginListService $pluginListService, private PluginMetadataService $pluginMetadataService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('download:plugins:partial')
            ->setAliases(['plugins:partial'])
            ->setDescription('Pulls a partial number of plugins based on the full list of plugins')
            ->addArgument('num-to-pull', InputArgument::REQUIRED, 'Number of plugins to pull')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset to start pulling from', 0)
            ->AddOption('versions', null, InputOption::VALUE_OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always("Running command {$this->getName()}");

        $this->startTimer();
        $numVersions = $input->getOption('versions');
        $numToPull   = (int) $input->getArgument('num-to-pull');
        $offset      = (int) $input->getArgument('offset');

        $this->debug('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getUpdatedListOfItems([]);

        $totalPlugins = count($pluginsToUpdate);

        $this->debug($totalPlugins . ' plugins to download...');

        if ($totalPlugins === 0) {
            $this->success('No plugins to download...exiting...');
            return Command::SUCCESS;
        }

        if ($offset > $totalPlugins) {
            $this->failure('Offset is greater than total plugins...exiting...');
            return Command::FAILURE;
        }
        $this->info('Limiting plugin download to ' . $numToPull . ' plugins... (offset by ' . $offset . ')');
        $pluginsToUpdate = array_slice($pluginsToUpdate, $offset, $numToPull);

        $processes = [];

        foreach ($pluginsToUpdate as $plugin => $versions) {
            $versions    = $this->determineVersionsToDownload($plugin, $versions, $numVersions);
            $versionList = implode(',', $versions);

            if (empty($versionList)) {
                $this->notice('No versions found for ' . $plugin . '...skipping...');
                continue;
            }

            $command = [
                './assetgrabber',
                'internal:plugin-download',
                $plugin,
                $versionList,
                $numVersions,
            ];

            if ($input->getOption('force-download')) {
                $command[] = '-f';
            }

            $process = new Process($command);
            $process->start();
            $processes[] = $process;

            if (count($processes) >= 24) {
                $this->debug('Max processes reached...waiting for space...');
                $stats = ProcessWaitUtil::wait($processes);
                $this->processStats($stats);
                $this->info($stats);
                $this->debug('Process ended; starting another...');
            }
        }

        $this->debug('Waiting for all processes to finish...');

        $stats = ProcessWaitUtil::waitAtEndOfScript($processes);
        foreach ($stats as $stat) {
            $this->processStats($stat);
            $this->info($stat);
        }

        $this->debug('All processes finished!');

        $this->endTimer();

        $this->always($this->getRunInfo($this->getCalculatedStats()));

        return Command::SUCCESS;
    }

    /**
     * @param string[] $versions
     * @return array<int, string>
     */
    private function determineVersionsToDownload(string $plugin, array $versions, string $numToDownload): array
    {
        switch ($numToDownload) {
            case 'all':
                $download = $versions;
                break;

            case 'latest':
                $download = [VersionUtil::getLatestVersion($versions)];
                break;

            default:
                $download = VersionUtil::limitVersions(VersionUtil::sortVersions($versions), (int) $numToDownload);
        }

        return $this->pluginMetadataService->getUnprocessedVersions($plugin, $download);
    }
}
