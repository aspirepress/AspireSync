<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Commands\Meta\AbstractMetaSyncCommand;
use AspirePress\AspireSync\Integrations\Wordpress\PluginRequest;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Utilities\StringUtil;
use Saloon\Http\Request;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MetaSyncPluginsCommand extends AbstractMetaSyncCommand
{
    public function __construct(
        PluginListService $listService,
        PluginMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($listService, $meta, $api);
    }

    protected Resource $resource = Resource::Plugin;

    protected function configure(): void
    {
        $this->setName('meta:sync:plugins')
            ->setDescription('Fetches the meta data of the plugins')
            ->addOption(
                'update-all',
                'u',
                InputOption::VALUE_NONE,
                'Update all plugin meta-data; otherwise, we only update what has changed'
            )
            ->addOption(
                'skip-newer-than-secs',
                null,
                InputOption::VALUE_REQUIRED,
                'Skip downloading metadata pulled more recently than N seconds'
            )
            ->addOption(
                'plugins',
                null,
                InputOption::VALUE_OPTIONAL,
                'List of plugins (separated by commas) to explicitly update'
            );
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
            $this->fetch($slug);
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

    protected function makeRequest($slug): Request
    {
        return new PluginRequest($slug);
    }
}
