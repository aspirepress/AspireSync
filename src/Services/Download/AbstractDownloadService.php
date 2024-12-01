<?php

declare(strict_types=1);

namespace App\Services\Download;

use App\Integrations\Wordpress\DownloadRequest;
use App\Integrations\Wordpress\WordpressDownloadConnector;
use App\Services\Metadata\MetadataServiceInterface;
use App\Utilities\ArrayUtil;
use Exception;
use Generator;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Saloon\Exceptions\Request\RequestException;
use Saloon\Http\Response;

abstract class AbstractDownloadService implements DownloadServiceInterface
{
    public const MAX_CONCURRENT_REQUESTS = 10;

    public function __construct(
        protected readonly MetadataServiceInterface $meta,
        protected readonly WordpressDownloadConnector $connector,
        protected readonly FilesystemOperator $filesystem,
        protected readonly LoggerInterface $log,
    ) {}

    abstract protected function getCategory(): string;

    /** @return array<string, ?int> */
    private function getFileListings(): array
    {
        return ArrayUtil::fromEntries(
            $this->filesystem->listContents('/' . $this->getCategory())
                ->map(fn($entry) => ['/' . $entry->path(), $entry->lastModified()]),
        );
    }

    /** @param iterable<array{string, string}> $slugsAndVersions */
    public function downloadBatch(iterable $slugsAndVersions, bool $force = false): void
    {
        $pool    = $this->connector->pool(
            requests: $this->generateRequests($slugsAndVersions, $force),
            concurrency: static::MAX_CONCURRENT_REQUESTS,
            responseHandler: $this->onResponse(...),
            exceptionHandler: $this->onError(...),
        );
        $promise = $pool->send();
        $promise->wait();
    }

    private function onResponse(Response $saloonResponse): void
    {
        $response   = $saloonResponse->getPsrResponse();
        $request    = $saloonResponse->getRequest();
        $remotePath = $request->remotePath ?? throw new Exception('Missing remotePath in request');
        $localPath  = $request->localPath ?? throw new Exception('Missing localPath in request');
        $slug       = $request->slug ?? throw new Exception('Missing slug in request');
        $version    = $request->version ?? throw new Exception('Missing version in request');

        $this->meta->markProcessed($slug, $version);

        $contents = $response->getBody()->getContents();
        if (!$contents) {
            $this->log->warning("Empty response", compact('remotePath', 'localPath'));
            return;
        }
        $this->log->info("Downloaded", compact('slug', 'version', 'localPath'));
        $this->filesystem->write($localPath, $contents);
    }

    protected function onError(RequestException $exception): void
    {
        $saloonResponse = $exception->getResponse();
        $response       = $saloonResponse->getPsrResponse();
        $request        = $saloonResponse->getRequest();
        $slug           = $request->slug ?? throw new Exception('Missing slug in request');
        $version        = $request->version ?? throw new Exception('Missing version in request');
        $code           = $response->getStatusCode();
        $reason         = $response->getReasonPhrase();
        $message        = $exception->getMessage();

        $this->log->error("Download error", compact('slug', 'code', 'reason', 'message'));

        if ($code === 404) {
            $this->meta->markProcessed($slug, $version);
        }
    }

    /** @param iterable<array{string, string}> $slugsAndVersions */
    private function generateRequests(iterable $slugsAndVersions, bool $force): Generator
    {
        $category = $this->getCategory();
        $files    = $force ? [] : $this->getFileListings();

        foreach ($slugsAndVersions as [$slug, $version]) {
            $url = $this->meta->getDownloadUrl($slug, $version);
            if (!$url) {
                $this->log->warning("No download URL", compact('slug', 'version'));
            }

            $localPath = "/$category/$slug.$version.zip";

            if (array_key_exists($localPath, $files)) {
                // XXX YUCK side effects
                $this->log->debug("File already downloaded", compact('localPath', 'slug', 'version'));
                $this->meta->markProcessed($slug, $version);
                continue;
            }
            $remotePath = \Safe\preg_replace('#^https?://.*?/#', '/', $url);
            yield new DownloadRequest($remotePath, $localPath, $slug, $version);
        }
    }
}
