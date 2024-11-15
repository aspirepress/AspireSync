<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Plugins;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsMetaCommand extends AbstractBaseCommand
{
    public function __construct(
        private PluginListService $listService,
        private PluginMetadataService $meta,
        private WpEndpointClientInterface $wpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('plugins:meta')
            ->setDescription('Fetches the meta data of the plugins')
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all plugin meta-data; otherwise, we only update what has changed')
            ->addOption('skip-newer-than-secs', null, InputOption::VALUE_REQUIRED, 'Skip downloading metadata pulled more recently than N seconds')
            ->addOption('plugins', null, InputOption::VALUE_OPTIONAL, 'List of plugins (separated by commas) to explicitly update');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->always("Running command {$this->getName()}");
        $this->startTimer();

        $slugs   = StringUtil::explodeAndTrim($input->getOption('plugins') ?? '');
        $min_age = (int) $input->getOption('skip-newer-than-secs') ?: null;

        $this->debug('Getting list of plugins...');
        $pending = $this->listService->getItems($slugs, $min_age);

        if (count($pending) === 0) {
            $this->success('No plugin metadata to download. exiting.');
            return Command::SUCCESS;
        }

        $this->info("Downloading metadata for " . count($pending) . " plugins");

        foreach ($pending as $slug => $versions) {
            $status = $this->meta->getStatus($slug);
            if (in_array($status, ['closed', 'not-found'], true)) {
                $this->info("$slug ... skipped ($status)");
                continue;
            }
            $this->fetchPluginDetails($input, $output, $slug, $versions);
        }

        if ($input->getOption('plugins')) {
            $this->debug("Not saving revision when --plugins was specified");
        } else {
            $revision = $this->listService->preserveRevision();
            $this->debug("Updated current revision to $revision");
        }
        $this->endTimer();

        return Command::SUCCESS;
    }

    /** @param string[] $versions */
    private function fetchPluginDetails(InputInterface $input, OutputInterface $output, string $slug, array $versions): void
    {
        try {
            $data = $this->wpClient->getPluginMetadata($slug);
        } catch (\Exception $e) {
            // If Guzzle runs out of retries or some non-recoverable exception happens, just scream and move on.
            $this->error("$slug ... ERROR: {$e->getMessage()}");
            return;
        }
        $error = $data['error'] ?? null;

        $this->meta->save($data);

        if (! empty($data['versions'])) {
            $this->info("$slug ... [" . count($data['versions']) . ' versions]');
        } elseif (isset($data['version'])) {
            $this->info("$slug ... [1 version]");
        } elseif (isset($data['skipped'])) {
            $this->info((string) $data['skipped']);
        } elseif ($error) {
            if ($error === 'closed') {
                $this->info("$slug ... [closed]");
            } else {
                $this->error(message: "$slug ... ERROR: $error");
            }
            if ('429' === (string) $error) {
                $this->progressiveBackoff();
                $this->fetchPluginDetails($input, $output, $slug, $versions);
                return;
            }
        } else {
            $this->info("$slug ... No versions found");
        }

        $this->iterateProgressiveBackoffLevel(self::ITERATE_DOWN);
    }
}
