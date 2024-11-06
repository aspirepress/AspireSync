<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Flysystem;

use Laminas\ServiceManager\ServiceManager;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class FilesystemFactory
{
    public function __invoke(ServiceManager $serviceManager, string $service): Filesystem
    {
        $config       = $serviceManager->get('config');
        $adapterClass = $config['flysystem'][$service] ?? LocalFilesystemAdapter::class;
        return new Filesystem($serviceManager->get($adapterClass));
    }
}
