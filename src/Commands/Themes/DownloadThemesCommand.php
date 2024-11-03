<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use AspirePress\AspireSync\Utilities\GetItemsFromSourceTrait;
use AspirePress\AspireSync\Utilities\ListManagementUtil;
use AspirePress\AspireSync\Utilities\ProcessWaitUtil;
use AspirePress\AspireSync\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DownloadThemesCommand extends AbstractBaseCommand
{
    use GetItemsFromSourceTrait;

    public function __construct(private ThemeListService $themeListService, private ThemesMetadataService $themeMetadataService, private StatsMetadataService $statsMetadataService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('download:themes')
            ->setAliases(['themes:grab'])
            ->setDescription('Grabs themes (with number of specified versions or explicitly specified themes) from the origin repo')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption('themes', null, InputOption::VALUE_OPTIONAL, 'List of themes to request')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists')
            ->addOption('download-all', 'd', InputOption::VALUE_NONE, 'Download all themes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always('Running command ' . $this->getName());
        $this->startTimer();
        $numVersions = $input->getArgument('num-versions');
        $themesList  = $input->getOption('themes');

        if ($themesList) {
            $themesList = ListManagementUtil::explodeCommaSeparatedList($themesList);
        }

        $this->info('Getting list of themes...');
        if ($input->getOption('download-all')) {
            $themesToUpdate = $this->themeListService->getUpdatedListOfItems($themesList, 'default');
        } else {
            $themesToUpdate = $this->themeListService->getUpdatedListOfItems($themesList);
        }

        $this->info(count($themesToUpdate) . ' themes to download...');
        if (count($themesToUpdate) === 0) {
            $this->always('No themes to download...exiting...');
            return Command::SUCCESS;
        }

        $processes = [];

        foreach ($themesToUpdate as $theme => $versions) {
            $versions = $this->determineVersionsToDownload($theme, $versions, $numVersions);

            $versionList = implode(',', $versions);

            if (empty($versionList)) {
                $this->notice('No downloadable versions found for ' . $theme . '...skipping...');
                continue;
            }

            $command = [
                'aspiresync',
                'internal:theme-download',
                $theme,
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
                $this->info($stats);
                $this->processStats($stats);
                $this->debug('Process ended; starting another...');
            }
        }

        $this->info('Waiting for all processes to finish...');

        $stats = ProcessWaitUtil::waitAtEndOfScript($processes);
        foreach ($stats as $stat) {
            $this->processStats($stat);
            $this->info($stat);
        }

        $this->info('All processes finished...');

        // Output statistics
        $this->endTimer();
        $this->always($this->getRunInfo($this->getCalculatedStats()));
        $this->statsMetadataService->logStats($this->getName(), $this->stats);
        return Command::SUCCESS;
    }

    /**
     * @param string[] $versions
     * @return array<int, string>
     */
    private function determineVersionsToDownload(string $theme, array $versions, string $numToDownload): array
    {
        $download = match ($numToDownload) {
            'all' => $versions,
            'latest' => [VersionUtil::getLatestVersion($versions)],
            default => VersionUtil::limitVersions(VersionUtil::sortVersions($versions), (int) $numToDownload),
        };
        return $this->themeMetadataService->getUnprocessedVersions($theme, $download);
    }
}
