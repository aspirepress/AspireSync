<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Plugins\PluginDownloadService;
use AspirePress\AspireSync\Utilities\VersionUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsDownloadSingleCommand extends AbstractBaseCommand
{
    public function __construct(private PluginDownloadService $downloadService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugins:download:single')
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

        $response = $this->downloadService->download($slug, $version, $force);
        // TODO: fire a PluginDownloaded event with response
        $this->always("{$response['url']} {$response['status']} {$response['message']}");

        return Command::SUCCESS;
    }
}
