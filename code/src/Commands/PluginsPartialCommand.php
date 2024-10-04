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

class PluginsPartialCommand extends AbstractBaseCommand
{
    /** @var array<string, int> */
    private array $stats = [
        'success'      => 0,
        'failed'       => 0,
        'not_modified' => 0,
        'not_found'    => 0,
        'total'        => 0,
    ];

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
        $this->startTimer();
        $numVersions = $input->getOption('versions');
        $numToPull   = (int) $input->getArgument('num-to-pull');
        $offset      = (int) $input->getArgument('offset');

        $output->writeln('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getPluginUpdateList([]);

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
            $process->start();
            $processes[] = $process;

            if (count($processes) >= 24) {
                $output->writeln('Max processes reached...waiting for space...');
                $stats = ProcessWaitUtil::wait($processes);
                $this->processStats($stats);
                $output->writeln($stats);
                $output->writeln('Process ended; starting another...');
            }
        }

        $output->writeln('Waiting for all processes to finish...');

        $stats = ProcessWaitUtil::waitAtEndOfScript($processes);
        foreach ($stats as $stat) {
            $this->processStats($stat);
            $output->writeln($stat);
        }

        $output->writeln('All processes finished!');

        $this->endTimer();
        $elapsed = $this->getElapsedTime();

        $output->writeln("Took $elapsed seconds...");
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
        foreach ($matches[1] as $k => $v) {
            switch ($v) {
                case '304 Not Modified':
                    $this->stats['not_modified'] += (int) $matches[2][$k];
                    $this->stats['total']        += (int) $matches[2][$k];
                    break;

                case '200 OK':
                    $this->stats['success'] += (int) $matches[2][$k];
                    $this->stats['total']   += (int) $matches[2][$k];
                    break;

                case '404 Not Found':
                    $this->stats['not_found'] += (int) $matches[2][$k];
                    $this->stats['total']     += (int) $matches[2][$k];
                    break;

                default:
                    $this->stats['failed'] += (int) $matches[2][$k];
                    $this->stats['total']  += (int) $matches[2][$k];
            }
        }
    }
}
