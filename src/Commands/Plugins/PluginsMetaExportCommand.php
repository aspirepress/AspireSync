<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsMetaExportCommand extends AbstractBaseCommand
{
    public function __construct(
        private PluginMetadataService $meta,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugins:meta:export')
            ->setDescription('Dumps metadata of all plugins in jsonl format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();

        foreach ($this->meta->exportAllMetadata() as $json) {
            echo $json;
        }

        return Command::SUCCESS;
    }
}
