<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class GrabPluginsCommand extends Command
{
    public function __construct(private PluginListService $pluginListService, private PluginDownloadService  $downloadService)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('plugins:grab')
            ->setDescription('Grabs all plugins from the origin repo');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getPluginList();
        $output->writeln(count($pluginsToUpdate).' plugins to update...');
        $processes = [];
        foreach ($pluginsToUpdate as $plugin => $versions) {
            $process = new Process([
                './update',
                'plugins:download',
                $plugin
            ]);
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

        $this->pluginListService->preservePluginList($pluginsToUpdate);

        return Command::SUCCESS;
    }
}