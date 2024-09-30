<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\ThemeListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ThemesGrabCommand extends Command
{
    public function __construct(private ThemeListService $themeListService)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('themes:grab')
            ->setDescription('Grabs themes (with number of specified versions or explicitly specified themes) from the origin repo')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'all')
            ->addOption('themes', null, InputOption::VALUE_OPTIONAL, 'List of themes to request')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $numVersions = $input->getArgument('num-versions');
        $themes = $input->getOption('themes');

        if ($themes) {
            $themes = explode(',', $themes);
            foreach($themes as $k => $theme) {
                $themes[$k] = trim($theme);
            }
        }

        $output->writeln('Getting list of themes...');
        $themesToUpdate = $this->themeListService->getThemeList($themes);
        $output->writeln(count($themesToUpdate).' themes to download...');

        if (count($themesToUpdate) === 0) {
            $output->writeln('No themes to download...exiting...');
            return Command::SUCCESS;
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
                'internal:theme-download',
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
                    unset($processes[$k]);}
            }
        }

        $output->writeln('All processes finished...');

        $this->themeListService->preserveThemeList($themesToUpdate);

        return Command::SUCCESS;
    }
}
