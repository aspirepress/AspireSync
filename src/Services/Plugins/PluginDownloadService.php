<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Services\AbstractDownloadService;
use AspirePress\AspireSync\Services\Interfaces\DownloadServiceInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Flysystem\Filesystem;

class PluginDownloadService extends AbstractDownloadService implements DownloadServiceInterface
{
    public function __construct(
        PluginMetadataService $meta,
        GuzzleClient $guzzle,
        Filesystem $filesystem,
    ) {
        parent::__construct($meta, $guzzle, $filesystem);
    }

    protected function getCategory(): string
    {
        return 'plugins';
    }
}
