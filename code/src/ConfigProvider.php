<?php

declare(strict_types=1);

namespace AssetGrabber;

use AssetGrabber\Commands\Plugins\DownloadPluginsCommand;
use AssetGrabber\Commands\Plugins\DownloadPluginsPartialCommand;
use AssetGrabber\Commands\Plugins\InternalPluginDownloadCommand;
use AssetGrabber\Commands\Plugins\MetaDownloadPluginsCommand;
use AssetGrabber\Commands\Plugins\MetaImportPluginsCommand;
use AssetGrabber\Commands\Themes\DownloadThemesCommand;
use AssetGrabber\Commands\Themes\DownloadThemesPartialCommand;
use AssetGrabber\Commands\Themes\InternalThemeDownloadCommand;
use AssetGrabber\Commands\Themes\MetaDownloadThemesCommand;
use AssetGrabber\Commands\Themes\MetaImportThemesCommand;
use AssetGrabber\Commands\UtilCleanDataCommand;
use AssetGrabber\Commands\UtilUploadCommand;
use AssetGrabber\Factories\ExtendedPdoFactory;
use AssetGrabber\Factories\Flysystem\AwsS3V3AdapterFactory;
use AssetGrabber\Factories\Flysystem\FilesystemFactory;
use AssetGrabber\Factories\Flysystem\LocalFilesystemAdapterFactory;
use AssetGrabber\Factories\Plugins\DownloadPluginsCommandFactory;
use AssetGrabber\Factories\Plugins\DownloadPluginsPartialCommandFactory;
use AssetGrabber\Factories\Plugins\InternalPluginDownloadCommandFactory;
use AssetGrabber\Factories\Plugins\MetaDownloadPluginsCommandFactory;
use AssetGrabber\Factories\Plugins\MetaImportPluginsCommandFactory;
use AssetGrabber\Factories\Plugins\PluginDownloadFromWpServiceFactory;
use AssetGrabber\Factories\Plugins\PluginListServiceFactory;
use AssetGrabber\Factories\Plugins\PluginMetadataServiceFactory;
use AssetGrabber\Factories\RevisionMetadataServiceFactory;
use AssetGrabber\Factories\Themes\DownloadThemesCommandFactory;
use AssetGrabber\Factories\Themes\DownloadThemesPartialCommandFactory;
use AssetGrabber\Factories\Themes\InternalThemeDownloadCommandFactory;
use AssetGrabber\Factories\Themes\MetaDownloadThemesCommandFactory;
use AssetGrabber\Factories\Themes\MetaImportThemesCommandFactory;
use AssetGrabber\Factories\Themes\ThemeDownloadFromWpServiceFactory;
use AssetGrabber\Factories\Themes\ThemeListServiceFactory;
use AssetGrabber\Factories\Themes\ThemeMetadataServiceFactory;
use AssetGrabber\Factories\UtilUploadCommandFactory;
use AssetGrabber\Services\Plugins\PluginDownloadFromWpService;
use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\RevisionMetadataService;
use AssetGrabber\Services\Themes\ThemeDownloadFromWpService;
use AssetGrabber\Services\Themes\ThemeListService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Aura\Sql\ExtendedPdoInterface;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

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
                UtilCleanDataCommand::class => UtilCleanDataCommand::class,
            ],
            'factories'  => [
                // Services
                PluginDownloadFromWpService::class => PluginDownloadFromWpServiceFactory::class,
                ThemeListService::class            => ThemeListServiceFactory::class,
                PluginListService::class           => PluginListServiceFactory::class,
                ExtendedPdoInterface::class        => ExtendedPdoFactory::class,
                PluginMetadataService::class       => PluginMetadataServiceFactory::class,
                RevisionMetadataService::class     => RevisionMetadataServiceFactory::class,
                ThemesMetadataService::class       => ThemeMetadataServiceFactory::class,
                ThemeDownloadFromWpService::class => ThemeDownloadFromWpServiceFactory::class,

                // Commands
                DownloadPluginsCommand::class        => DownloadPluginsCommandFactory::class,
                InternalPluginDownloadCommand::class => InternalPluginDownloadCommandFactory::class,
                DownloadPluginsPartialCommand::class => DownloadPluginsPartialCommandFactory::class,
                MetaDownloadPluginsCommand::class    => MetaDownloadPluginsCommandFactory::class,
                MetaImportPluginsCommand::class      => MetaImportPluginsCommandFactory::class,
                UtilUploadCommand::class             => UtilUploadCommandFactory::class,
                MetaDownloadThemesCommand::class     => MetaDownloadThemesCommandFactory::class,
                MetaImportThemesCommand::class       => MetaImportThemesCommandFactory::class,
                DownloadThemesCommand::class         => DownloadThemesCommandFactory::class,
                InternalThemeDownloadCommand::class => InternalThemeDownloadCommandFactory::class,
                DownloadThemesPartialCommand::class => DownloadThemesPartialCommandFactory::class,
                // Flysystem
                Filesystem::class             => FilesystemFactory::class,
                LocalFilesystemAdapter::class => LocalFilesystemAdapterFactory::class,
                AwsS3V3Adapter::class         => AwsS3V3AdapterFactory::class,

                // Util Flysystem Config
                'util:upload:plugins' => FilesystemFactory::class,
            ],
        ];
    }
}
