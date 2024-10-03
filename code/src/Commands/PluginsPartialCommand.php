<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\PluginListService;
use AssetGrabber\Utilities\ProcessWaitUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PluginsPartialCommand extends Command
{
    public function __construct(private PluginListService $pluginListService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugins:partial')
            ->setDescription('Pulls a partial number of plugins based on the full list of plugins')
            ->addArgument('num-to-pull', InputArgument::REQUIRED, 'Number of plugins to pull')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset to start pulling from', 0)
            ->AddOption('versions', null, InputOption::VALUE_OPTIONAL, 'Number of versions to request', 'all')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numVersions = $input->getOption('versions');
        $numToPull   = (int) $input->getArgument('num-to-pull');
        $offset      = (int) $input->getArgument('offset');

        $output->writeln('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getPluginListForAction([], 'plugins:partial');

        $totalPlugins = count($pluginsToUpdate);

        $output->writeln($totalPlugins . ' plugins to download...');

        if ($totalPlugins === 0) {
            $output->writeln('No plugins to download...exiting...');
            return Command::SUCCESS;
        }

        if ($totalPlugins > $numToPull) {
            $output->writeln('Limiting plugin download to ' . $numToPull . ' plugins... (offset by ' . $offset . ')');
            $pluginsToUpdate = array_slice($pluginsToUpdate, $offset, $numToPull);
        }

        $processes = [];

        foreach ($pluginsToUpdate as $plugin => $versions) {
            if (! empty($versions)) {
                $versionList = implode(',', $versions);
            } else {
                $updatedVersionList       = $this->pluginListService->getVersionsForPlugin($plugin);
                $versionList              = implode(',', $updatedVersionList);
                $pluginsToUpdate[$plugin] = $updatedVersionList;
            }

            if (empty($versionList)) {
                $output->writeln('No versions found for ' . $plugin . '...skipping...');
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
                $output->writeln('Max processes reached...waiting for space...');
                $output->writeln(ProcessWaitUtil::wait($processes));
                $output->writeln('Process ended; starting another...');
            }
        }

        $output->writeln('Waiting for all processes to finish...');

        ProcessWaitUtil::waitAtEndOfScript($processes);

        $output->writeln('All processes finished...');

        $this->pluginListService->preserveRevision('plugins:partial');

        return Command::SUCCESS;
    }
}
