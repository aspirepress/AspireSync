<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Utilities\HasStats;
use AspirePress\AspireSync\Utilities\ProcessWaitUtil;
use AspirePress\AspireSync\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PluginsDownloadPartialCommand extends AbstractBaseCommand
{
    use HasStats;

    public function __construct(private PluginListService $pluginListService, private PluginMetadataService $pluginMetadataService, private StatsMetadataService $statsMetadataService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugins:download:partial')
            ->setDescription('Pulls a partial number of plugins based on the full list of plugins')
            ->addArgument('num-to-pull', InputArgument::REQUIRED, 'Number of plugins to pull')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset to start pulling from', 0)
            ->AddOption('versions', null, InputOption::VALUE_OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists')
            ->addOption('download-all', 'd', InputOption::VALUE_NONE, 'Download all plugins (limited by offset and limit)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always("Running command {$this->getName()}");

        $this->startTimer();
        $numVersions = $input->getOption('versions');
        $numToPull   = (int) $input->getArgument('num-to-pull');
        $offset      = (int) $input->getArgument('offset');

        $this->debug('Getting list of plugins...');
        if ($input->getOption('download-all')) {
            $pluginsToUpdate = $this->pluginListService->getUpdatedListOfItems([], 'default');
        } else {
            $pluginsToUpdate = $this->pluginListService->getUpdatedListOfItems([]);
        }

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
                'aspiresync',
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
                // $this->debug('Max processes reached...waiting for space...');
                $stats = ProcessWaitUtil::wait($processes);
                $this->processStats($stats);
                $this->info($stats);
                // $this->debug('Process ended; starting another...');
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
        $this->statsMetadataService->logStats($this->getName(), $this->stats);

        return Command::SUCCESS;
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
