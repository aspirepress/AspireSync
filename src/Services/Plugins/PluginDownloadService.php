<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use League\Flysystem\Filesystem;

class PluginDownloadService implements DownloadServiceInterface
{
    public function __construct(
        private PluginMetadataService $pluginMeta,
        private GuzzleClient $guzzle,
        private Filesystem $filesystem,
    ) {
    }

    /** @return array{status:int|null, message:string, url:string|null} */
    public function download(string $slug, string $version, bool $force = false): array
    {
        $url = $this->pluginMeta->getDownloadUrl($slug, $version);
        if (! $url) {
            return ['message' => 'No download URL found for $slug $version', 'status' => null, 'url' => null];
        }

        $ret = fn(string $message, int $status = 200) => ['message' => $message, 'status' => $status, 'url' => $url];

        $fs   = $this->filesystem;
        $path = "/plugins/$slug.$version.zip";

        if ($fs->fileExists($path) && ! $force) {
            $this->pluginMeta->setVersionToDownloaded($slug, $version);
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
            $this->pluginMeta->setVersionToDownloaded($slug, $version);
            return $ret($response->getReasonPhrase() ?: 'OK', $response->getStatusCode() ?: 200);
        } catch (ClientException $e) {
            $fs->delete($path);
            if (method_exists($e, 'getResponse')) {
                $response = $e->getResponse();
                if ($response->getStatusCode() === 404) {
                    $this->pluginMeta->setVersionToDownloaded($slug, $version);
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
