<?php

declare(strict_types=1);

namespace App\Commands\Sync\Meta;

use App\Commands\AbstractBaseCommand;
use App\Integrations\Wordpress\WordpressApiConnector;
use App\ResourceType;
use App\Services\Metadata\MetadataServiceInterface;
use App\Utilities\RegexUtil;
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

    private ?int $limit = null;
    private int $generated = 0;

    public function __construct(
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
                'empty-slugs-ok',
                null,
                InputOption::VALUE_NONE,
                'Exit successfully if slugs list is empty',
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
                'limit',
                null,
                InputOption::VALUE_REQUIRED,
                "Stop after fetching N $category",
            )
            ->addOption(
                'slugs',
                null,
                InputOption::VALUE_REQUIRED,
                "List of $category (separated by commas) to explicitly update",
            )
            ->addOption(
                'slugs-from',
                null,
                InputOption::VALUE_REQUIRED,
                "File containing list of $category to explicitly update (one per line)",
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = $this->resource->plural();
        $this->log->notice("Running command {$this->getName()}");
        $this->startTimer();

        $this->clobber = (bool) $input->getOption('update-all');
        $limit = $input->getOption('limit');
        $this->limit = ($limit === null) ? null : (int) $limit; // don't convert null to 0

        $pulledCutoff = 0;
        $checkedCutoff = 0;
        if ($timestamp = $input->getOption('skip-pulled-after')) {
            $pulledCutoff = static::parseTimestamp($timestamp);
        }
        if ($timestamp = $input->getOption('skip-checked-after')) {
            $checkedCutoff = static::parseTimestamp($timestamp);
        }

        $slugs = $input->getOption('slugs') ?? '';

        if ($infile = $input->getOption('slugs-from')) {
            $slugs .= \Safe\file_get_contents($infile);
        }

        $requested = array_fill_keys(StringUtil::explodeAndTrim($slugs), []);

        if (!$requested) {
            if (!$input->getOption('empty-slugs-ok')) {
                $this->log->error("No slugs specified -- exiting.");
                return Command::FAILURE;
            } else {
                $this->log->info("No slugs specified -- exiting.");
                return Command::SUCCESS;
            }
        }

        if ($requested) {
            $count = count($requested);
            $this->log->debug("Getting $count requested $category...");
            $toUpdate = $requested;
        }
        if ($pulledCutoff) {
            $toUpdate = array_diff_key($toUpdate, $this->meta->getPulledAfter($pulledCutoff));
            $this->log->debug("after --skip-pulled-after $pulledCutoff : " . count($toUpdate));
        }
        if ($checkedCutoff) {
            $toUpdate = array_diff_key($toUpdate, $this->meta->getCheckedAfter($checkedCutoff));
            $this->log->debug("after --skip-checked-after $checkedCutoff : " . count($toUpdate));
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

        $this->endTimer();

        return Command::SUCCESS;
    }

    /**
     * @param iterable<array-key> $slugs
     * @return Generator<Request>
     */
    protected function generateRequests(iterable $slugs): Generator
    {
        foreach ($slugs as $slug) {
            if ($this->limit !== null && $this->generated >= $this->limit) {
                $this->log->info("Limit reached -- downloading $this->limit {$this->resource->plural()}");
                return;
            }
            yield $this->makeRequest((string) $slug);
            $this->generated++;
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

    protected static function parseTimestamp(string $timestamp): int
    {
        if (RegexUtil::match('/^\d+$/', $timestamp)) {
            return (int) $timestamp;
        }
        return \Safe\strtotime($timestamp);
    }
}
