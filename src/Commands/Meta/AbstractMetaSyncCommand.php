<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Interfaces\ListServiceInterface;
use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use AspirePress\AspireSync\Utilities\StringUtil;
use Exception;
use Saloon\Http\Request;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Safe\json_decode;

abstract class AbstractMetaSyncCommand extends AbstractBaseCommand
{
    public function __construct(
        protected readonly ListServiceInterface $listService,
        protected readonly MetadataServiceInterface $meta,
        protected readonly WordpressApiConnector $api,
        protected readonly Resource $resource,
    ) {
        parent::__construct();
    }

    abstract protected function makeRequest($slug): Request;

    protected function configure(): void
    {
        $type = $this->resource->value;
        $category = $type . 's';
        $this->setName("meta:sync:$category")
            ->setDescription("Fetches meta data of all new and changed $category")
            ->addOption('update-all', 'u', InputOption::VALUE_NONE, 'Update all metadata; otherwise, we only update what has changed')
            ->addOption('skip-newer-than-secs', null, InputOption::VALUE_REQUIRED, 'Skip downloading metadata pulled more recently than N seconds')
            ->addOption($category, null, InputOption::VALUE_OPTIONAL, "List of $category (separated by commas) to explicitly update");
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $this->resource->value . 's';
        $this->writeMessage("Running command {$this->getName()}");
        $this->startTimer();

        $items  = StringUtil::explodeAndTrim($input->getOption($category) ?? '');
        $min_age = (int) $input->getOption('skip-newer-than-secs') ?: null;

        $this->debug("Getting list of $category...");
        $toUpdate = $this->listService->getItems($items, $min_age);
        $this->info(count($toUpdate) . " $category to download metadata for...");

        if (count($toUpdate) === 0) {
            $this->error('No metadata to download; exiting.');
            return Command::SUCCESS;
        }

        foreach ($toUpdate as $slug => $versions) {
            $this->fetch((string) $slug);
        }

        if ($input->getOption($category)) {
            $this->debug("Not saving revision when --$category was specified");
        } else {
            $revision = $this->listService->preserveRevision();
            $this->debug("Updated current revision to $revision");
        }
        $this->endTimer();

        return Command::SUCCESS;
    }

    protected function fetch(string $slug): void
    {
        try {
            $this->log->debug("FETCH", ['slug' => $slug]);
            $request  = $this->makeRequest($slug);
            $reply    = $this->api->send($request);
            $response = $reply->getPsrResponse();

            $code   = $response->getStatusCode();
            $reason = $response->getReasonPhrase();
            $code !== 200 and $this->debug("$slug ... $code $reason");

            $metadata = json_decode($response->getBody()->getContents(), assoc: true);
            $status   = match ($code) {
                200 => 'open',
                404 => 'not-found',
                default => 'error',
            };
            $metadata = [
                'slug'   => $slug,
                'name'   => $slug,
                'status' => $status,
                ...$metadata,
            ];
        } catch (Exception $e) {
            $this->error("$slug ... ERROR: {$e->getMessage()}");
            return;
        }
        $error = $metadata['error'] ?? null;

        $this->meta->save($metadata);

        if (! empty($metadata['versions'])) {
            $this->info("$slug ... [" . count($metadata['versions']) . ' versions]');
        } elseif (isset($metadata['version'])) {
            $this->info("$slug ... [1 version]");
        } elseif (isset($metadata['skipped'])) {
            $this->info((string) $metadata['skipped']);
        } elseif ($error) {
            if ($error === 'closed') {
                $this->info("$slug ... [closed]");
            } elseif ($code === 404) {
                $this->info("$slug ... [not found]");
            } else {
                $this->error(message: "$slug ... ERROR: $error");
            }
            if ('429' === (string) $error) {
                $this->progressiveBackoff();
                $this->fetch($slug);
                return;
            }
        } else {
            $this->info("$slug ... No versions found");
        }

        $this->iterateProgressiveBackoffLevel(self::ITERATE_DOWN);
    }
}
