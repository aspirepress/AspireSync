<?php

declare(strict_types=1);

namespace AssetGrabber;

use AssetGrabber\Commands\InternalDownloadPluginsCommand;
use AssetGrabber\Commands\GrabPluginsCommand;
use AssetGrabber\Commands\PluginsPullPartialCommand;
use AssetGrabber\Factories\InternalDownloadPluginsCommandFactory;
use AssetGrabber\Factories\GrabPluginsCommandFactory;
use AssetGrabber\Factories\PluginsPullPartialCommandFactory;
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
                InternalDownloadPluginsCommand::class => InternalDownloadPluginsCommandFactory::class,
                PluginsPullPartialCommand::class => PluginsPullPartialCommandFactory::class,
            ]
        ];
    }
}
