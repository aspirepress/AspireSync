<?php

declare(strict_types=1);

namespace App\Services\Download;

use App\Integrations\Wordpress\WordpressDownloadConnector;
use App\Services\Metadata\PluginMetadataService;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PluginDownloadService extends AbstractDownloadService
{
    public function __construct(
        PluginMetadataService $meta,
        WordpressDownloadConnector $connector,
        #[Autowire(service: 'fs.storage')]
        FilesystemOperator $filesystem,
        LoggerInterface $log,
    ) {
        parent::__construct($meta, $connector, $filesystem, $log);
    }

    protected function getCategory(): string
    {
        return 'plugins';
    }
}
