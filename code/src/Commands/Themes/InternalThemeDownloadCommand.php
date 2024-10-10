<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Themes\ThemeDownloadFromWpService;
use AspirePress\AspireSync\Utilities\ListManagementUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InternalThemeDownloadCommand extends AbstractBaseCommand
{
    public function __construct(private ThemeDownloadFromWpService $service)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('internal:theme-download')
            ->setDescription('Download all versions of a given theme')
            ->setHidden(true)
            ->addArgument('theme', InputArgument::REQUIRED, 'Theme name')
            ->addArgument('version-list', InputArgument::REQUIRED, 'List of versions to download')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to download', 'all')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $theme       = $input->getArgument('theme');
        $numVersions = $input->getArgument('num-versions');

        $this->debug('Determining versions of ' . $theme . '...');
        $versions = ListManagementUtil::explodeCommaSeparatedList($input->getArgument('version-list'));

        $versionCount = $this->versionCountBasedOnRequestedNumber($versions, $numVersions);
        $this->info('Downloading ' . $versionCount . ' versions...');
        $responses = $this->service->download($theme, $versions, $numVersions, $input->getOption('force'));
        foreach ($responses as $responseCode => $versions) {
            $this->always($theme . ' ' . $responseCode . ': ' . count($versions));
        }

        return Command::SUCCESS;
    }

    /**
     * @param array<int, string> $versions
     */
    private function versionCountBasedOnRequestedNumber(array $versions, string|int $numToDownload): int
    {
        switch ($numToDownload) {
            case 'all':
                return count($versions);
            case 'latest':
                return 1;
            default:
                return count($versions) > $numToDownload ? (int) $numToDownload : count($versions);
        }
    }
}
