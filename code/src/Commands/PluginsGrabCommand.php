<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class PluginsGrabCommand extends Command
{
    public function __construct(private PluginListService $pluginListService)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('plugins:grab')
            ->setDescription('Grabs plugins (with number of specified versions or explicitly specified plugins) from the origin repo')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'all')
            ->addOption('plugins', null, InputOption::VALUE_OPTIONAL, 'List of plugins to request')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numVersions = $input->getArgument('num-versions');
        $pluginList = $input->getOption('plugins');

        if ($pluginList) {
            $pluginList = explode(',', $pluginList);
            foreach($pluginList as $k => $plugin) {
                $pluginList[$k] = trim($plugin);
            }
        }

        $output->writeln('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getPluginList($pluginList);
        $output->writeln(count($pluginsToUpdate).' plugins to download...');

        if (count($pluginsToUpdate) === 0) {
            $output->writeln('No plugins to download...exiting...');
            return Command::SUCCESS;
        }

        $processes = [];

        foreach ($pluginsToUpdate as $plugin => $versions) {
            if (!empty($versions)) {
                $versionList = implode(',', $versions);
            } else {
                $updatedVersionList = $this->pluginListService->getVersionsForPlugin($plugin);
                $versionList = implode(',', $updatedVersionList);
                $pluginsToUpdate[$plugin] = $updatedVersionList;

            }

            if (empty($versionList)) {
                $output->writeln('No versions found for '.$plugin.'...skipping...');
                continue;
            }

            $command = [
                './assetgrabber',
                'internal:plugin-download',
                $plugin,
                $versionList,
                $numVersions
            ];

            if ($input->getOption('force-download')) {
                $command[] = '-f';
            }

            $process = new Process($command);
            $process->start(function ($type, $buffer) use ($output) { $output->write($buffer); });
            $processes[] = $process;

            $loopCount = 0;
            while (count($processes) >= 24) {
                if (($loopCount % 1000000) === 0 || $loopCount === 0) {
                    $output->writeln('Max processes reached...waiting for space...');
                }
                foreach ($processes as $k => $process) {
                    if (!$process->isRunning()) {
                        unset($processes[$k]);
                        $output->writeln('Process ended, starting another...');
                        $loopCount = 0;
                    }
                }
                $loopCount++;
            }
        }

        $output->writeln('Waiting for all processes to finish...');

        while(count($processes) > 0) {
            foreach ($processes as $k => $process) {
                if (!$process->isRunning()) {
                    unset($processes[$k]);}
            }
        }

        $output->writeln('All processes finished...');

        $this->pluginListService->preservePluginList($pluginsToUpdate);

        return Command::SUCCESS;
    }
}
