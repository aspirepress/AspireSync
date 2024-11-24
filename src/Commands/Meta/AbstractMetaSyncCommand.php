<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Commands\AbstractBaseCommand;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\ResourceType;
use AspirePress\AspireSync\Services\List\ListServiceInterface;
use AspirePress\AspireSync\Services\Metadata\MetadataServiceInterface;
use AspirePress\AspireSync\Utilities\StringUtil;
use Exception;
use Generator;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\json_decode;

abstract class AbstractMetaSyncCommand extends AbstractBaseCommand
{
    public const MAX_CONCURRENT_REQUESTS = 10;

    public function __construct(
        protected readonly ListServiceInterface $listService,
        protected readonly MetadataServiceInterface $meta,
        protected readonly WordpressApiConnector $api,
        protected readonly ResourceType $resource,
    ) {
        parent::__construct();
    }

    abstract protected function makeRequest(string $slug): Request;

    protected function configure(): void
    {
        $category = $this->resource->value . 's';
        $this->setName("meta:sync:$category")
            ->setDescription("Fetches meta data of all new and changed $category")
            ->addOption(
                'update-all',
                'u',
                InputOption::VALUE_NONE,
                'Update all metadata; otherwise, we only update what has changed'
            )
            ->addOption(
                'skip-newer-than-secs',
                null,
                InputOption::VALUE_REQUIRED,
                'Skip downloading metadata pulled more recently than N seconds'
            )
            ->addOption(
                $category,
                null,
                InputOption::VALUE_OPTIONAL,
                "List of $category (separated by commas) to explicitly update"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $this->resource->value . 's';
        $this->writeMessage("Running command {$this->getName()}");
        $this->startTimer();

        $items   = StringUtil::explodeAndTrim($input->getOption($category) ?? '');
        $min_age = (int) $input->getOption('skip-newer-than-secs') ?: null;

        $this->debug("Getting list of $category...");
        $toUpdate = $this->listService->getItems($items, $min_age);
        $this->info(count($toUpdate) . " $category to download metadata for...");

        if (count($toUpdate) === 0) {
            $this->info('No metadata to download; exiting.');
            return Command::SUCCESS;
        }

        $pool = $this->api->pool(
            requests: $this->generateRequests(array_keys($toUpdate)),
            concurrency: static::MAX_CONCURRENT_REQUESTS,
            responseHandler: $this->onResponse(...),
            exceptionHandler: $this->onError(...)
        );

        $promise = $pool->send();
        $promise->wait();

        if ($input->getOption($category)) {
            $this->debug("Not saving revision when --$category was specified");
        } else {
            $revision = $this->listService->preserveRevision();
            $this->debug("Updated current revision to $revision");
        }
        $this->endTimer();

        return Command::SUCCESS;
    }

    protected function generateRequests(array $slugs): Generator
    {
        foreach ($slugs as $slug) {
            yield $this->makeRequest((string) $slug);
        }
    }

    protected function onResponse(Response $saloonResponse): void
    {
        $response = $saloonResponse->getPsrResponse();
        $request  = $saloonResponse->getRequest();
        $slug     = $request->slug ?? throw new Exception('Missing slug in request');

        try {
            $metadata = json_decode($response->getBody()->getContents(), assoc: true);
            $metadata = [
                'slug'   => $slug,
                'name'   => $slug,
                'status' => 'open',
                ...$metadata,
            ];
        } catch (Exception $e) {
            $this->error("$slug ... ERROR: {$e->getMessage()}");
            return;
        }

        if (! empty($metadata['versions'])) {
            $this->info("$slug ... [" . count($metadata['versions']) . ' versions]');
        } elseif (isset($metadata['version'])) {
            $this->info("$slug ... [1 version]");
        } elseif (isset($metadata['skipped'])) {
            $this->info((string) $metadata['skipped']);
        } else {
            $this->info("$slug ... No versions found");
        }

        $this->meta->save($metadata);
    }

    protected function onError(Exception $exception): void
    {
        if (! $exception instanceof RequestException) {
            $this->error($exception->getMessage());
            return;
        }
        $saloonResponse = $exception->getResponse();
        $response       = $saloonResponse->getPsrResponse();
        $request        = $saloonResponse->getRequest();
        $slug           = $request->slug ?? throw new Exception('Missing slug in request');
        $code           = $response->getStatusCode();
        $reason         = $response->getReasonPhrase();

        $metadata = json_decode($response->getBody()->getContents(), assoc: true);
        $error    = $metadata['error'] ?? null;

        $status = match ($code) {
            404 => $error === 'closed' ? 'closed' : 'not-found',
            default => 'error',
        };

        if ($status === 'closed') {
            $this->info("$slug ... [closed]");
        } else {
            $this->error("$slug ... $code $reason");
        }

        $this->meta->save(['slug' => $slug, 'name' => $slug, 'status' => $status, ...$metadata]);
    }
}
