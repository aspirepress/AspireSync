<?php

declare(strict_types=1);

namespace App\Commands\Download;

use App\Commands\AbstractBaseCommand;
use App\Services\Download\PluginDownloadService;
use App\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadPluginsSingleCommand extends AbstractBaseCommand
{
    public function __construct(private PluginDownloadService $downloadService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('download:plugins:single')
            ->setDescription('Download an individual plugin version')
            ->addArgument('plugin', InputArgument::REQUIRED, 'Plugin name')
            ->addArgument('version', InputArgument::REQUIRED, 'Plugin version')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slug    = $input->getArgument('plugin');
        $version = $input->getArgument('version');
        $force   = $input->getOption('force');

        [$version, $message] = VersionUtil::cleanVersion($version);
        if (! $version) {
            $this->error($message);
            return Command::FAILURE;
        }

        $this->downloadService->downloadBatch([[$slug, $version]], $force);

        return Command::SUCCESS;
    }
}
