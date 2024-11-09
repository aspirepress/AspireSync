<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Themes;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemeMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThemesMetaExportCommand extends AbstractBaseCommand
{
    public function __construct(
        private ThemeMetadataService $meta,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('themes:meta:export')
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
