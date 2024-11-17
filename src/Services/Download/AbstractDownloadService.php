<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Download;

use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\Filesystem;

abstract class AbstractDownloadService
{
    public function __construct(
        protected readonly MetadataServiceInterface $meta,
        protected readonly GuzzleClient $guzzle,
        protected readonly Filesystem $filesystem,
    ) {
    }

    abstract protected function getCategory(): string;

    /** @return array{status:int|null, message:string, url:string|null} */
    public function download(string $slug, string $version, bool $force = false): array
    {
        $category = $this->getCategory();
        $url      = $this->meta->getDownloadUrl($slug, $version);
        if (! $url) {
            return ['message' => 'No download URL found for $slug $version', 'status' => null, 'url' => null];
        }

        $ret = fn(string $message, int $status = 200) => ['message' => $message, 'status' => $status, 'url' => $url];

        $fs   = $this->filesystem;
        $path = "/$category/$slug.$version.zip";

        if ($fs->fileExists($path) && ! $force) {
            $this->meta->markProcessed($slug, $version);
            return $ret('Not Modified', 304);
        }

        try {
            $options  = ['headers' => ['User-Agent' => 'AspireSync/0.5'], 'allow_redirects' => true];
            $response = $this->guzzle->request('GET', $url, $options);
            $contents = $response->getBody()->getContents();
            if (! $contents) {
                return $ret('Empty response', 204); // code indicates success, but no state gets written
            }
            $fs->write($path, $contents);
            $this->meta->markProcessed($slug, $version);
            return $ret($response->getReasonPhrase() ?: 'OK', $response->getStatusCode() ?: 200);
        } catch (ClientException $e) {
            $fs->delete($path);
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 404) {
                    $this->meta->markProcessed($slug, $version);
                }
                return $ret($response->getReasonPhrase(), $response->getStatusCode());
            } else {
                return $ret($e->getMessage(), (int) $e->getCode());
            }
        } catch (Exception $e) {
            $fs->delete($path);
            return $ret($e->getMessage(), (int) $e->getCode());
        }
    }
}
