<?php

declare(strict_types=1);

namespace App\Services\Download;

use App\Integrations\Wordpress\WordpressDownloadConnector;
use App\Services\Metadata\ThemeMetadataService;
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
