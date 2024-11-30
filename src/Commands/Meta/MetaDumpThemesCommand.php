<?php

declare(strict_types=1);

namespace App\Commands\Meta;

use App\Commands\AbstractBaseCommand;
use App\Services\Metadata\ThemeMetadataService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MetaDumpThemesCommand extends AbstractBaseCommand
{
    public function __construct(
        private ThemeMetadataService $meta,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('meta:dump:themes')
            ->setDescription('Dumps metadata of all themes in jsonl format');
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
