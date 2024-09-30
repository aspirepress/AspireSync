<?php

declare(strict_types=1);

namespace AssetGrabber;

use AssetGrabber\Commands\InternalDownloadPluginsCommand;
use AssetGrabber\Commands\GrabPluginsCommand;
use AssetGrabber\Commands\InternalDownloadThemesCommand;
use AssetGrabber\Commands\PluginsPullPartialCommand;
use AssetGrabber\Commands\ThemesGrabCommand;
use AssetGrabber\Commands\ThemesPartialCommand;
use AssetGrabber\Factories\InternalDownloadPluginsCommandFactory;
use AssetGrabber\Factories\GrabPluginsCommandFactory;
use AssetGrabber\Factories\InternalDownloadThemesCommandFactory;
use AssetGrabber\Factories\PluginsPullPartialCommandFactory;
use AssetGrabber\Factories\ThemesGrabCommandFactory;
use AssetGrabber\Factories\ThemesPartialCommandFactory;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\ThemeDownloadService;
use AssetGrabber\Services\ThemeListService;

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
                ThemeListService::class => ThemeListService::class,
                ThemeDownloadService::class => ThemeDownloadService::class,
            ],
            'factories' => [
                // Services

                // Commands
                GrabPluginsCommand::class => GrabPluginsCommandFactory::class,
                ThemesGrabCommand::class => ThemesGrabCommandFactory::class,
                InternalDownloadThemesCommand::class => InternalDownloadThemesCommandFactory::class,
                InternalDownloadPluginsCommand::class => InternalDownloadPluginsCommandFactory::class,
                PluginsPullPartialCommand::class => PluginsPullPartialCommandFactory::class,
                ThemesPartialCommand::class => ThemesPartialCommandFactory::class,
            ]
        ];
    }
}
