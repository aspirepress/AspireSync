<?php

declare(strict_types=1);

namespace App\Commands\Sync\Download;

use App\Commands\AbstractBaseCommand;
use App\Services\Download\ThemeDownloadService;
use App\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadThemesSingleCommand extends AbstractBaseCommand
{
    public function __construct(private ThemeDownloadService $downloadService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('sync:download:themes:single')
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
            $this->log->error($message);
            return Command::FAILURE;
        }

        $this->downloadService->downloadBatch([[$slug, $version]], $force);

        return Command::SUCCESS;
    }
}
