<?php

declare(strict_types=1);

namespace AssetGrabber\Commands\Themes;

use AssetGrabber\Commands\AbstractBaseCommand;
use AssetGrabber\Services\Themes\ThemeListService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use AssetGrabber\Utilities\GetItemsFromSourceTrait;
use AssetGrabber\Utilities\ListManagementUtil;
use AssetGrabber\Utilities\ProcessWaitUtil;
use AssetGrabber\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class DownloadThemesCommand extends AbstractBaseCommand
{
    use GetItemsFromSourceTrait;

    public function __construct(private ThemeListService $themeListService, private ThemesMetadataService $themeMetadataService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('download:themes')
            ->setAliases(['themes:grab'])
            ->setDescription('Grabs themes (with number of specified versions or explicitly specified plugins) from the origin repo')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to request', 'latest')
            ->addOption('themes', null, InputOption::VALUE_OPTIONAL, 'List of plugins to request')
            ->addOption('force-download', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();
        $numVersions = $input->getArgument('num-versions');
        $themesList  = $input->getOption('themes');

        if ($themesList) {
            $themesList = ListManagementUtil::explodeCommaSeparatedList($themesList);
        }

        $output->writeln('Getting list of themes...');
        $themesToUpdate = $this->themeListService->getUpdatedListOfItems($themesList);

        $output->writeln(count($themesToUpdate) . ' themes to download...');
        if (count($themesToUpdate) === 0) {
            $output->writeln('No themes to download...exiting...');
            return Command::SUCCESS;
        }

        $processes = [];

        foreach ($themesToUpdate as $theme => $versions) {
            $versions = $this->determineVersionsToDownload($theme, $versions, $numVersions);

            $versionList = implode(',', $versions);

            if (empty($versionList)) {
                $output->writeln('No downloadable versions found for ' . $theme . '...skipping...');
                continue;
            }

            $command = [
                './assetgrabber',
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
                $output->writeln('Max processes reached...waiting for space...');
                $stats = ProcessWaitUtil::wait($processes);
                $output->writeln($stats);
                $this->processStats($stats);
                $output->writeln('Process ended; starting another...');
            }
        }

        $output->writeln('Waiting for all processes to finish...');

        $stats = ProcessWaitUtil::waitAtEndOfScript($processes);
        foreach ($stats as $stat) {
            $this->processStats($stat);
            $output->writeln($stat);
        }

        $output->writeln('All processes finished...');

        // Output statistics
        $this->endTimer();
        $output->writeln($this->getRunInfo($this->getCalculatedStats()));
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

        return $this->themeMetadataService->getUnprocessedVersions($theme, $download);
    }
}
