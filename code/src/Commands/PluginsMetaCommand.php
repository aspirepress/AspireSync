<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use AssetGrabber\Services\PluginListService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsMetaCommand extends AbstractBaseCommand
{
    /** @var array<string, int> */
    private array $stats = [
        'plugins'  => 0,
        'versions' => 0,
        'errors'   => 0,
    ];

    public function __construct(private PluginListService $pluginListService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugins:meta')
            ->setDescription('Fetches the meta data of the plugins')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all plugin meta-data; otherwise, we only update what has changed')
            ->addOption('plugins', null, InputOption::VALUE_OPTIONAL, 'List of plugins (separated by commas) to explicitly update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->startTimer();
        $plugins         = [];
        $pluginsToUpdate = $input->getOption('plugins');
        if ($pluginsToUpdate) {
            $plugins = explode(',', $pluginsToUpdate);
            array_walk($plugins, function (&$value) {
                $value = trim($value);
            });
        }

        $output->writeln('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getItemsForAction($plugins, 'plugins:meta');
        $output->writeln(count($pluginsToUpdate) . ' plugins to download metadata for...');

        if (count($pluginsToUpdate) === 0) {
            $output->writeln('No plugin metadata to download...exiting...');
            return Command::SUCCESS;
        }

        $processes = [];

        foreach ($pluginsToUpdate as $plugin => $versions) {
            $this->stats['plugins']++;
            $data = $this->pluginListService->getItemMetadata($plugin);

            if (isset($data['versions']) && ! empty($data['versions'])) {
                $output->writeln("Plugin $plugin has " . count($data['versions']) . ' versions');
                $this->stats['versions'] += count($data['versions']);
            } elseif (isset($data['version'])) {
                $output->writeln("Plugin $plugin has 1 version");
                $this->stats['versions'] += 1;
            } elseif (isset($data['error'])) {
                $output->writeln("Error fetching metadata for plugin $plugin: " . $data['error']);
                $this->stats['errors']++;
            } else {
                $output->writeln("No versions found for plugin $plugin");
            }
        }

        $this->pluginListService->preserveRevision('plugins:meta');
        $this->endTimer();

        $output->writeln($this->getRunInfo($this->calculateStats()));
        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function calculateStats(): array
    {
        return [
            'Stats:',
            'Total Plugins Found:    ' . $this->stats['plugins'],
            'Total Versions Found:   ' . $this->stats['versions'],
            'Total Failed Downloads: ' . $this->stats['errors'],
        ];
    }
}
