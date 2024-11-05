<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Themes\ThemeDownloadFromWpService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InternalThemeDownloadCommand extends AbstractBaseCommand
{
    public function __construct(private ThemeDownloadFromWpService $downloadService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('internal:theme-download')
            ->setDescription('Download all versions of a given theme')
            ->setHidden()
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
        $versions = StringUtil::explodeAndTrim($input->getArgument('version-list') ?? '');
        $count    = match ($numVersions) {
            'all' => count($versions),
            'latest' => 1,
            default => min(count($versions), (int) $numVersions),
        };
        $this->info("Downloading $count versions...");

        $responses = $this->downloadService->download($theme, $versions, $numVersions, $input->getOption('force'));
        foreach ($responses as $responseCode => $versions) {
            $this->always($theme . ' ' . $responseCode . ': ' . $count);
        }
        return Command::SUCCESS;
    }
}
