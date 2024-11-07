<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Plugins\PluginDownloadFromWpService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InternalPluginDownloadCommand extends AbstractBaseCommand
{
    public function __construct(private PluginDownloadFromWpService $downloadService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('internal:plugin-download')
            ->setDescription('Download all versions of a given plugin')
            ->setHidden()
            ->addArgument('plugin', InputArgument::REQUIRED, 'Plugin name')
            ->addArgument('version-list', InputArgument::REQUIRED, 'List of versions to download')
            ->addArgument('num-versions', InputArgument::OPTIONAL, 'Number of versions to download', 'all')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force download even if file exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $slug        = $input->getArgument('plugin');
        $numVersions = $input->getArgument('num-versions');
        $versions    = StringUtil::explodeAndTrim($input->getArgument('version-list'));

        $this->debug("[{$this->getName()}] $slug...");
        $versionsToDownload = match ($numVersions) {
            'all' => count($versions),
            'latest' => 1,
            default => min(count($versions), (int) $numVersions),
        };

        $this->debug("Downloading $versionsToDownload versions");
        $responses = $this->downloadService->download($slug, $versions, $input->getOption('force'));

        foreach ($responses as $responseCode => $versions) {
            $this->always("$slug $responseCode: " . count($versions));
        }

        return Command::SUCCESS;
    }
}
