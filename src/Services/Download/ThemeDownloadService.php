<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Download;

use AspirePress\AspireSync\Integrations\Wordpress\WordpressDownloadConnector;
use AspirePress\AspireSync\Services\Metadata\ThemeMetadataService;
use League\Flysystem\Filesystem;
use Psr\Log\LoggerInterface;

class ThemeDownloadService extends AbstractDownloadService
{
    public function __construct(
        ThemeMetadataService $meta,
        WordpressDownloadConnector $connector,
        Filesystem $filesystem,
        LoggerInterface $log,
    ) {
        parent::__construct($meta, $connector, $filesystem, $log);
    }

    protected function getCategory(): string
    {
        return 'themes';
    }
}
