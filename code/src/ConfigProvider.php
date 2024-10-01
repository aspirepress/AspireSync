<?php

declare(strict_types=1);

namespace AssetGrabber;

use AssetGrabber\Commands\InternalPluginDownloadCommand;
use AssetGrabber\Commands\InternalThemeDownloadCommand;
use AssetGrabber\Commands\PluginsGrabCommand;
use AssetGrabber\Commands\PluginsPartialCommand;
use AssetGrabber\Commands\ThemesGrabCommand;
use AssetGrabber\Commands\ThemesPartialCommand;
use AssetGrabber\Factories\InternalPluginDownloadCommandFactory;
use AssetGrabber\Factories\InternalThemeDownloadCommandFactory;
use AssetGrabber\Factories\PluginsGrabCommandFactory;
use AssetGrabber\Factories\PluginsPartialCommandFactory;
use AssetGrabber\Factories\ThemesGrabCommandFactory;
use AssetGrabber\Factories\ThemesPartialCommandFactory;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\ThemeDownloadService;
use AssetGrabber\Services\ThemeListService;

class ConfigProvider
{
    /**
     * @return array<string, array<string, string[]>>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array<string, string[]>
     */
    private function getDependencies(): array
    {
        return [
            'invokables' => [
                PluginDownloadService::class => PluginDownloadService::class,
                PluginListService::class     => PluginListService::class,
                ThemeListService::class      => ThemeListService::class,
                ThemeDownloadService::class  => ThemeDownloadService::class,
            ],
            'factories'  => [
                // Services

                // Commands
                PluginsGrabCommand::class            => PluginsGrabCommandFactory::class,
                ThemesGrabCommand::class             => ThemesGrabCommandFactory::class,
                InternalThemeDownloadCommand::class  => InternalThemeDownloadCommandFactory::class,
                InternalPluginDownloadCommand::class => InternalPluginDownloadCommandFactory::class,
                PluginsPartialCommand::class         => PluginsPartialCommandFactory::class,
                ThemesPartialCommand::class          => ThemesPartialCommandFactory::class,
            ],
        ];
    }
}
