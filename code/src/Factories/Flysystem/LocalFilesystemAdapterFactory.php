<?php

declare(strict_types=1);

namespace AssetGrabber\Factories\Flysystem;

use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystemAdapterFactory
{
    public function __invoke(): LocalFilesystemAdapter
    {
        return new LocalFilesystemAdapter('/opt/assetgrabber/data/localtest');
    }
}