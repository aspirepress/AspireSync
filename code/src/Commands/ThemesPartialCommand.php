<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\ThemeListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ThemesPartialCommand extends Command
{
    public function __construct(private ThemeListService $themeListService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('themes:partial')
            ->setDescription('Pulls a partial number of themes based on the full list of themes')
            ->addArgument('num-to-pull', InputArgument::REQUIRED, 'Number of themes to pull')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset to start pulling from', 0)
            ->AddOption('versions', null, InputOption::VALUE_OPTIONAL, 'Number of versions to request', 'all')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numVersions = $input->getOption('versions');
        $numToPull = (int)$input->getArgument('num-to-pull');
        $offset = (int)$input->getArgument('offset');

        $output->writeln('Getting list of themes...');
        $themesToUpdate = $this->themeListService->getThemeList();

        $totalThemes = count($themesToUpdate);

        $output->writeln($totalThemes.' themes to download...');

        if ($totalThemes === 0) {
            $output->writeln('No themes to download...exiting...');
            return Command::SUCCESS;
        }

        if ($totalThemes > $numToPull) {
            $output->writeln('Limiting theme download to '.$numToPull.' themes... (offset by ' . $offset . ')');
            $themesToUpdate = array_slice($themesToUpdate, $offset, $numToPull);
        }

        $processes = [];

        foreach ($themesToUpdate as $theme => $versions) {
            if (!empty($versions)) {
                $versionList = implode(',', $versions);
            } else {
                $updatedVersionList = $this->themeListService->getVersionsForTheme($theme);
                $versionList = implode(',', $updatedVersionList);
                $themesToUpdate[$theme] = $updatedVersionList;

            }

            if (empty($versionList)) {
                $output->writeln('No versions found for '.$theme.'...skipping...');
                continue;
            }

            $command = [
                './assetgrabber',
                'internal:plugin-download',
                $theme,
                $versionList,
                $numVersions
            ];

            if ($input->getOption('force-download')) {
                $command[] = '-f';
            }

            $process = new Process($command);
            $process->start();
            $processes[] = $process;

            if (count($processes) >= 24) {
                $output->writeln('Max processes reached...waiting for space...');
            }
            while (count($processes) >= 24) {
                foreach ($processes as $k => $process) {
                    if (!$process->isRunning()) {
                        $processOutput = $process->getOutput();
                        $output->writeln($processOutput);
                        unset($processes[$k]);
                        $output->writeln('Process ended, starting another...');
                    }
                }
            }
        }

        $output->writeln('Waiting for all processes to finish...');

        while(count($processes) > 0) {
            foreach ($processes as $k => $process) {
                if (!$process->isRunning()) {
                    $processOutput = $process->getOutput();
                    $output->writeln($processOutput);
                    unset($processes[$k]);
                    $output->writeln(count($processes) . ' remaining to finish...');
                }
            }
        }

        $output->writeln('All processes finished...');

        $this->themeListService->preserveThemeList($themesToUpdate);

        return Command::SUCCESS;
    }
}
