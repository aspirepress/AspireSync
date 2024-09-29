<?php

declare(strict_types=1);

namespace AssetGrabber;

use AssetGrabber\Commands\DownloadPluginVersionsCommand;
use AssetGrabber\Commands\GrabPluginsCommand;
use AssetGrabber\Factories\DownloadPluginVersionsCommandFactory;
use AssetGrabber\Factories\GrabPluginsCommandFactory;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    private function getDependencies(): array
    {
        return [
            'invokables' => [
                PluginDownloadService::class => PluginDownloadService::class,
                PluginListService::class => PluginListService::class,
            ],
            'factories' => [
                // Services

                // Commands
                GrabPluginsCommand::class => GrabPluginsCommandFactory::class,
                DownloadPluginVersionsCommand::class => DownloadPluginVersionsCommandFactory::class,
            ]
        ];
    }
}