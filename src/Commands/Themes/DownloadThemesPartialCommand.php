<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use AspirePress\AspireSync\Utilities\GetItemsFromSourceTrait;
use AspirePress\AspireSync\Utilities\ProcessWaitUtil;
use AspirePress\AspireSync\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DownloadThemesPartialCommand extends AbstractBaseCommand
{
    use GetItemsFromSourceTrait;

    public function __construct(private ThemeListService $themeListService, private ThemesMetadataService $themesMetadataService, private StatsMetadataService $statsMetadataService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('download:themes:partial')
            ->setAliases(['themes:partial'])
            ->setDescription('Pulls a partial number of themes based on the full list of themes')
            ->addArgument('num-to-pull', InputArgument::REQUIRED, 'Number of themes to pull')
            ->addArgument('offset', InputArgument::OPTIONAL, 'Offset to start pulling from', 0)
            ->AddOption('versions', null, InputOption::VALUE_OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists')
            ->addOption('download-all', 'd', InputOption::VALUE_NONE, 'Download all themes (limited by offset and limit)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always("Running command {$this->getName()}");
        $this->startTimer();
        $numVersions = $input->getOption('versions');
        $numToPull   = (int) $input->getArgument('num-to-pull');
        $offset      = (int) $input->getArgument('offset');

        $this->debug('Getting list of themes...');
        if ($input->getOption('download-all')) {
            $themesToUpdate = $this->themeListService->getUpdatedListOfItems([], 'default');
        } else {
            $themesToUpdate = $this->themeListService->getUpdatedListOfItems([]);
        }

        $totalthemes = count($themesToUpdate);

        $this->debug($totalthemes . ' themes to download...');

        if ($totalthemes === 0) {
            $this->success('No themes to download...exiting...');
            return Command::SUCCESS;
        }

        if ($offset > $totalthemes) {
            $this->failure('Offset is greater than total themes...exiting...');
            return Command::FAILURE;
        }
        $this->info('Limiting theme download to ' . $numToPull . ' themes... (offset by ' . $offset . ')');
        $themesToUpdate = array_slice($themesToUpdate, $offset, $numToPull);

        $processes = [];

        foreach ($themesToUpdate as $theme => $versions) {
            $versions    = $this->determineVersionsToDownload($theme, $versions, $numVersions);
            $versionList = implode(',', $versions);

            if (empty($versionList)) {
                $this->info('No versions found for ' . $theme . '...skipping...');
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
        $this->statsMetadataService->logStats($this->getName(), $this->stats);
        return Command::SUCCESS;
    }

    /**
     * @param string[] $versions
     * @return array<int, string>
     */
    private function determineVersionsToDownload(string $theme, array $versions, string $numToDownload): array
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

        return $this->themesMetadataService->getUnprocessedVersions($theme, $download);
    }
}
