<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadPluginVersionsCommand extends Command
{
    public function __construct(private PluginListService $listService, private PluginDownloadService $service)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('plugins:download')
            ->setDescription('Download all versions of a given plugin')
            ->addArgument('plugin', InputArgument::REQUIRED, 'Plugin name');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $plugin = $input->getArgument('plugin');

        $output->writeln('Determining versions of ' . $plugin . '...');
        $versions = $this->listService->getVersionsForPlugin($plugin);
        $output->writeln('Downloading ' . count($versions) . ' versions...');
        $responses = $this->service->download($plugin, $versions, $output);
        foreach ($responses as $v => $response) {
            $output->writeln("$plugin v$v: $response");
        }

        return Command::SUCCESS;
    }
}