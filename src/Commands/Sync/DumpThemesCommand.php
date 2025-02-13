<?php

declare(strict_types=1);

namespace App\Commands\Sync;

use App\Commands\AbstractBaseCommand;
use App\Services\Metadata\ThemeMetadataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DumpThemesCommand extends AbstractBaseCommand
{
    public function __construct(
        private ThemeMetadataService $meta,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('sync:dump:themes')
            ->setDescription('Dumps metadata of all themes in jsonl format')
            ->addOption('after', null, InputOption::VALUE_REQUIRED, 'Dump only plugins synced after this date');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();

        $after = $input->getOption('after');
        $timestamp = $after ? \Safe\strtotime($after) : 0;

        foreach ($this->meta->exportAllMetadata($timestamp) as $json) {
            echo $json . PHP_EOL;
        }

        return Command::SUCCESS;
    }
}
