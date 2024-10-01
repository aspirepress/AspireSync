<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;


use AssetGrabber\Services\PluginListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsPullLatestRevCommand extends Command
{
    public function __construct(private PluginListService  $pluginListService)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('plugins:pull-latest-rev')
            ->setDescription('Pulls the latest revision from WP and stores it in the code')
            ->addOption('force', 'f', InputOption::VALUE_NONE);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $output->writeln('Getting latest plugin revision...');

        if (!$force) {
            $output->writeln('NOTE: USING CACHED DATA IF ABLE');
        }

        try {
            $revNum = $this->pluginListService->identifyCurrentRevision($force);
        } catch (\RuntimeException $e) {
            $output->writeln('There was a problem getting the latest revision.');
            $output->writeln($e->getMessage());
            return self::FAILURE;
        }

        $output->writeln('Success! Latest revision is ' . $revNum);
        return self::SUCCESS;
    }
}