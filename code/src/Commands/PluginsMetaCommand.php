<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\PluginListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsMetaCommand extends Command
{
    public function __construct(private PluginListService $pluginListService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugins:meta')
            ->setDescription('Fetches the meta data of the plugins')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all plugin meta-data; otherwise, we only update what has changed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getPluginListForAction(null, 'plugins:meta');
        $output->writeln(count($pluginsToUpdate) . ' plugins to download metadata for...');

        if (count($pluginsToUpdate) === 0) {
            $output->writeln('No plugin metadata to download...exiting...');
            return Command::SUCCESS;
        }

        $processes = [];

        foreach ($pluginsToUpdate as $plugin => $versions) {
            $data = $this->pluginListService->getPluginMetadata($plugin);

            if (isset($data['versions']) && ! empty($data['versions'])) {
                $output->writeln("Plugin $plugin has " . count($data['versions']) . ' versions');
            } elseif (isset($data['version'])) {
                $output->writeln("Plugin $plugin has 1 version");
            } elseif (isset($data['error'])) {
                $output->writeln("Error fetching metadata for plugin $plugin: " . $data['error']);
            } else {
                $output->writeln("No versions found for plugin $plugin");
            }
        }

        $this->pluginListService->preserveRevision('plugins:meta');

        return Command::SUCCESS;
    }
}
