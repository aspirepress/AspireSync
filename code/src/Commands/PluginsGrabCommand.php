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

class PluginsGrabCommand extends AbstractBaseCommand
{
    private array $stats = [
            'success' => 0,
            'failed' => 0,
            'not_modified' => 0,
            'not_found' => 0,
            'total' => 0,
        ];

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
        $this->startTimer();
        $numVersions = $input->getArgument('num-versions');
        $pluginList  = $input->getOption('plugins');

        if ($pluginList) {
            $pluginList = explode(',', $pluginList);
            foreach ($pluginList as $k => $plugin) {
                $pluginList[$k] = trim($plugin);
            }
        }

        $output->writeln('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getPluginUpdateList($pluginList);

        $output->writeln(count($pluginsToUpdate) . ' plugins to download...');
        if (count($pluginsToUpdate) === 0) {
            $output->writeln('No plugins to download...exiting...');
            return Command::SUCCESS;
        }

        $processes = [];

        foreach ($pluginsToUpdate as $plugin => $versions) {
            $versionList = implode(',', $versions);

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
            $process->start(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });
            $processes[] = $process;

            if (count($processes) >= 24) {
                $output->writeln('Max processes reached...waiting for space...');
                $stats = ProcessWaitUtil::wait($processes);
                $output->writeln($stats);
                $this->processStats($stats);
                $output->writeln('Process ended; starting another...');
            }
        }

        $output->writeln('Waiting for all processes to finish...');

        $stats = ProcessWaitUtil::waitAtEndOfScript($processes);
        foreach($stats as $stat) {
            $this->processStats($stat);
        }

        $output->writeln('All processes finished...');

        // Output statistics
        $this->endTimer();
        $time = $this->getElapsedTime();
        $output->writeln("Took $time seconds...");
        $output->writeln([
            'Stats:',
            'DL Succeeded: ' . $this->stats['success'],
            'DL Failed:    ' . $this->stats['failed'],
            'Not Modified: ' . $this->stats['not_modified'],
            'Not Found:    ' . $this->stats['not_found'],
            'Total:        ' . $this->stats['total'],
        ]);

        return Command::SUCCESS;
    }

    private function processStats(string $stats): void
    {
        preg_match_all('/[A-z\-_]+ ([0-9){3} [A-z ]+)\: ([0-9]+)/', $stats, $matches);
        foreach($matches[1] as $k => $v)
        {
            switch($v) {
                case '304 Not Modified':
                    $this->stats['not_modified'] += (int) $matches[2][$k];
                    $this->stats['total'] += (int) $matches[2][$k];
                    break;

                case '200 OK':
                    $this->stats['success'] += (int) $matches[2][$k];
                    $this->stats['total'] += (int) $matches[2][$k];
                    break;

                case '404 Not Found':
                    $this->stats['not_found'] += (int) $matches[2][$k];
                    $this->stats['total'] += (int) $matches[2][$k];
                    break;

                default:
                    $this->stats['failed'] += (int) $matches[2][$k];
                    $this->stats['total'] += (int) $matches[2][$k];
            }
        }
    }
}
