<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Flysystem;

use Laminas\ServiceManager\ServiceManager;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystemAdapterFactory
{
    public function __invoke(ServiceManager $serviceManager): LocalFilesystemAdapter
    {
        $config    = $serviceManager->get('config');
        $uploadDir = $config['local_filesystem']['upload_dir'];
        return new LocalFilesystemAdapter($uploadDir);
    }
}
