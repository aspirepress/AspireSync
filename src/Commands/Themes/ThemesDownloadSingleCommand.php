<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Themes\ThemeDownloadService;
use AspirePress\AspireSync\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThemesDownloadSingleCommand extends AbstractBaseCommand
{
    public function __construct(private ThemeDownloadService $downloadService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('themes:download:single')
            ->setDescription('Download an individual theme version')
            ->addArgument('theme', InputArgument::REQUIRED, 'Theme name')
            ->addArgument('version', InputArgument::REQUIRED, 'Theme version')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slug    = $input->getArgument('theme');
        $version = $input->getArgument('version');
        $force   = $input->getOption('force');

        [$version, $message] = VersionUtil::cleanVersion($version);
        if (! $version) {
            $this->error($message);
            return Command::FAILURE;
        }

        $response = $this->downloadService->download($slug, $version, $force);
        // TODO: fire a ThemeDownloaded event with response
        $this->always("{$response['url']} {$response['status']} {$response['message']}");

        return Command::SUCCESS;
    }
}
