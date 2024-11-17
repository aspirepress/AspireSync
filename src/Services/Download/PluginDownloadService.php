<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Download;

use AspirePress\AspireSync\Services\Download\AbstractDownloadService;
use AspirePress\AspireSync\Services\Download\DownloadServiceInterface;
use AspirePress\AspireSync\Services\Metadata\PluginMetadataService;
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
