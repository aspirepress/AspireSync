<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MetaDownloadPluginsCommand extends AbstractBaseCommand
{
    /** @var array<string, int> */
    private array $stats = [
        'plugins'      => 0,
        'versions'     => 0,
        'errors'       => 0,
        'rate_limited' => 0,
    ];

    public function __construct(private PluginListService $pluginListService, private StatsMetadataService $statsMetadataService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('meta:download:plugins')
            ->setAliases(['plugins:meta'])
            ->setDescription('Fetches the meta data of the plugins')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all plugin meta-data; otherwise, we only update what has changed')
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Skip downloading metadata files that already exist')
            ->addOption('plugins', null, InputOption::VALUE_OPTIONAL, 'List of plugins (separated by commas) to explicitly update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always("Running command {$this->getName()}");
        $this->startTimer();

        @mkdir('/opt/aspiresync/data/plugin-raw-data');

        $plugins = StringUtil::explodeAndTrim($input->getOption('plugins') ?? '');

        $this->debug('Getting list of plugins...');
        $pluginsToUpdate = $this->pluginListService->getItemsForAction($plugins, $this->getName());
        $this->debug(count($pluginsToUpdate) . ' plugins to download metadata for...');

        if (count($pluginsToUpdate) === 0) {
            $this->success('No plugin metadata to download...exiting...');
            return Command::SUCCESS;
        }

        foreach ($pluginsToUpdate as $plugin => $versions) {
            $this->fetchPluginDetails($input, $output, $plugin, $versions);
        }

        if ($input->getOption('plugins')) {
            $this->debug("Not saving revision when --plugins was specified");
        } else {
            $revision = $this->pluginListService->preserveRevision($this->getName());
            $this->debug("Updated current revision to $revision");
        }
        $this->endTimer();

        $this->always($this->getRunInfo($this->calculateStats()));
        $this->statsMetadataService->logStats($this->getName(), $this->stats);
        return Command::SUCCESS;
    }

    /** @return string[] */
    private function calculateStats(): array
    {
        return [
            'Stats:',
            'Total Plugins Found:    ' . $this->stats['plugins'],
            'Total Versions Found:   ' . $this->stats['versions'],
            'Total Failed Downloads: ' . $this->stats['errors'],
        ];
    }

    /** @param string[] $versions */
    private function fetchPluginDetails(InputInterface $input, OutputInterface $output, string $slug, array $versions): void
    {
        $filename = "/opt/aspiresync/data/plugin-raw-data/$slug.json";
        if (file_exists($filename) && $input->getOption('skip-existing')) {
            $this->info("$slug ... skipped (metadata file already exists)");
            return;
        }

        $this->stats['plugins']++;
        $data = $this->pluginListService->getItemMetadata($slug);

        if (! empty($data['versions'])) {
            $this->info("$slug ... [" . count($data['versions']) . ' versions]');
            $this->stats['versions'] += count($data['versions']);
        } elseif (isset($data['version'])) {
            $this->info("$slug ... [1 version]");
            $this->stats['versions'] += 1;
        } elseif (isset($data['skipped'])) {
            $this->info((string) $data['skipped']);
        } elseif (isset($data['error'])) {
            $this->error("$slug ... ERROR: " . $data['error']);
            if ('429' === (string) $data['error']) {
                $this->stats['rate_limited']++;
                $this->progressiveBackoff();
                $this->fetchPluginDetails($input, $output, $slug, $versions);
                return;
            }
            if ('Plugin not found.' === $data['error']) {
                $this->pluginListService->markItemNotFound($slug);
            }
            if ('Invalid plugin slug.' === $data['error']) {
                $this->pluginListService->markItemNotFound($slug);
            }
            $this->stats['errors']++;
        } else {
            $this->info("$slug ... No versions found");
        }

        $this->iterateProgressiveBackoffLevel(self::ITERATE_DOWN);
    }
}
