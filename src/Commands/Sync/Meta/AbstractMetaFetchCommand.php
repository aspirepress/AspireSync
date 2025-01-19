<?php

declare(strict_types=1);

namespace App\Commands\Sync\Meta;

use App\Commands\AbstractBaseCommand;
use App\Integrations\Wordpress\WordpressApiConnector;
use App\ResourceType;
use App\Services\List\ListServiceInterface;
use App\Services\Metadata\MetadataServiceInterface;
use App\Utilities\StringUtil;
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

abstract class AbstractMetaFetchCommand extends AbstractBaseCommand
{
    public const MAX_CONCURRENT_REQUESTS = 10;

    private bool $clobber = false;

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
        $category = $this->resource->plural();
        $this
            ->setName("sync:meta:fetch:$category")
            ->setDescription("Fetches meta data of all new and changed $category")
            ->addOption(
                'update-all',
                null,
                InputOption::VALUE_NONE,
                'Update all metadata; otherwise, we only update what has changed',
            )
            ->addOption(
                'skip-pulled-after',
                null,
                InputOption::VALUE_REQUIRED,
                'Skip downloading metadata with pulled timestamp > N',
            )
            ->addOption(
                'skip-checked-after',
                null,
                InputOption::VALUE_REQUIRED,
                'Skip downloading metadata with checked timestamp > N',
            )
            ->addOption(
                $category,
                null,
                InputOption::VALUE_REQUIRED,
                "List of $category (separated by commas) to explicitly update",
            );
        $this->listService->setName($this->getName());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $this->resource->plural();
        $this->log->notice("Running command {$this->getName()}");
        $this->startTimer();

        $this->clobber = (bool) $input->getOption('update-all');

        $requested = array_fill_keys(StringUtil::explodeAndTrim($input->getOption($category) ?? ''), []);
        $pulledCutoff = (int) $input->getOption('skip-pulled-after');
        $checkedCutoff = (int) $input->getOption('skip-checked-after');

        if ($requested) {
            $toUpdate = $requested;
        } else {
            $this->log->debug("Getting list of $category...");
            $toUpdate = $this->listService->getItems();
            $this->log->debug("Items to update: " . count($toUpdate));
            if ($pulledCutoff) {
                $toUpdate = array_diff_key($toUpdate, $this->meta->getPulledAfter($pulledCutoff));
                $this->log->debug("after --skip-pulled-after=$pulledCutoff: " . count($toUpdate));
            }
            if ($checkedCutoff) {
                $toUpdate = array_diff_key($toUpdate, $this->meta->getCheckedAfter($checkedCutoff));
                $this->log->debug("after --skip-checked-after=$checkedCutoff: " . count($toUpdate));
            }
        }

        if (count($toUpdate) === 0) {
            $this->log->info('No metadata to download; exiting.');
            return Command::SUCCESS;
        }

        $this->log->info(count($toUpdate) . " $category to download metadata for...");

        $pool = $this->api->pool(
            requests: $this->generateRequests(array_keys($toUpdate)),
            concurrency: static::MAX_CONCURRENT_REQUESTS,
            responseHandler: $this->onResponse(...),
            exceptionHandler: $this->onError(...),
        );

        $promise = $pool->send();
        $promise->wait();

        if ($requested) {
            $this->log->debug("Not saving revision when --$category was specified");
        } else {
            $revision = $this->listService->preserveRevision();
            $this->log->debug("Updated current revision to $revision");
        }
        $this->endTimer();

        return Command::SUCCESS;
    }

    /**
     * @param (string|int)[] $slugs
     * @return Generator<Request>
     */
    protected function generateRequests(iterable $slugs): Generator
    {
        foreach ($slugs as $slug) {
            yield $this->makeRequest((string) $slug);
        }
    }

    protected function onResponse(Response $saloonResponse): void
    {
        $slug = null;

        try {
            $response = $saloonResponse->getPsrResponse();
            $request = $saloonResponse->getRequest();
            $slug = $request->slug ?? throw new Exception('Missing slug in request');

            $metadata = json_decode($response->getBody()->getContents(), assoc: true);
            $metadata = [
                'slug' => $slug,
                'name' => $slug,
                'status' => 'open',
                ...$metadata,
            ];
            if (!empty($metadata['versions'])) {
                $this->log->info("$slug ... [" . count($metadata['versions']) . ' versions]');
            } elseif (isset($metadata['version'])) {
                $this->log->info("$slug ... [1 version]");
            } elseif (isset($metadata['skipped'])) {
                $this->log->info((string) $metadata['skipped']);
            } else {
                $this->log->info("$slug ... No versions found");
            }
            $this->meta->save($metadata, $this->clobber);
        } catch (Exception $e) {
            $this->log->error("$slug ... ERROR: {$e->getMessage()}");
            return;
        }
    }

    protected function onError(Exception $exception): void
    {
        $slug = null;
        try {
            if (!$exception instanceof RequestException) {
                $this->log->error($exception->getMessage());
                return;
            }
            $saloonResponse = $exception->getResponse();
            $response = $saloonResponse->getPsrResponse();
            $request = $saloonResponse->getRequest();
            $slug = $request->slug ?? throw new Exception('Missing slug in request');
            $code = $response->getStatusCode();
            $reason = $response->getReasonPhrase();

            $metadata = json_decode($response->getBody()->getContents(), assoc: true);
            $error = $metadata['error'] ?? null;

            $status = match ($code) {
                404 => $error === 'closed' ? 'closed' : 'not-found',
                default => 'error',
            };

            if ($status === 'closed') {
                $this->log->info("$slug ... [closed]");
            } else {
                $this->log->error("$slug ... $code $reason");
            }

            $this->meta->save(['slug' => $slug, 'name' => $slug, 'status' => $status, ...$metadata]);
        } catch (Exception $e) {
            $this->log->error("$slug ... ERROR: {$e->getMessage()}");
            return;
        }
    }
}
