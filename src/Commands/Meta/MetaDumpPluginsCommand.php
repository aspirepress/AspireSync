<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Metadata\PluginMetadataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MetaDumpPluginsCommand extends AbstractBaseCommand
{
    public function __construct(
        private PluginMetadataService $meta,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('meta:dump:plugins')
            ->setDescription('Dumps metadata of all plugins in jsonl format');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();

        foreach ($this->meta->exportAllMetadata() as $json) {
            echo $json . PHP_EOL;
        }

        return Command::SUCCESS;
    }
}
