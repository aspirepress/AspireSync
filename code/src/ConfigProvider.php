<?php

declare(strict_types=1);

namespace AssetGrabber;

use AssetGrabber\Commands\Plugins\InternalPluginDownloadCommand;
use AssetGrabber\Commands\Plugins\PluginsGrabCommand;
use AssetGrabber\Commands\Plugins\PluginsImportMetaCommand;
use AssetGrabber\Commands\Plugins\PluginsMetaCommand;
use AssetGrabber\Commands\Plugins\PluginsPartialCommand;
use AssetGrabber\Commands\InternalThemeDownloadCommand;
use AssetGrabber\Commands\PluginsPullLatestRevCommand;
use AssetGrabber\Commands\ThemesGrabCommand;
use AssetGrabber\Commands\ThemesPartialCommand;
use AssetGrabber\Commands\ThemesPullLatestRevCommand;
use AssetGrabber\Commands\UtilCleanDataCommand;
use AssetGrabber\Commands\UtilUploadCommand;
use AssetGrabber\Factories\ExtendedPdoFactory;
use AssetGrabber\Factories\Flysystem\AwsS3V3AdapterFactory;
use AssetGrabber\Factories\Flysystem\FilesystemFactory;
use AssetGrabber\Factories\Flysystem\LocalFilesystemAdapterFactory;
use AssetGrabber\Factories\InternalPluginDownloadCommandFactory;
use AssetGrabber\Factories\PluginDownloadFromWpServiceFactory;
use AssetGrabber\Factories\InternalThemeDownloadCommandFactory;
use AssetGrabber\Factories\PluginDownloadServiceFactory;
use AssetGrabber\Factories\PluginListServiceFactory;
use AssetGrabber\Factories\PluginMetadataServiceFactory;
use AssetGrabber\Factories\PluginsGrabCommandFactory;
use AssetGrabber\Factories\PluginsImportMetaCommandFactory;
use AssetGrabber\Factories\PluginsMetaCommandFactory;
use AssetGrabber\Factories\PluginsPartialCommandFactory;
use AssetGrabber\Factories\RevisionMetadataServiceFactory;
use AssetGrabber\Factories\UtilUploadCommandFactory;
use AssetGrabber\Services\PluginDownloadFromWpService;
use AssetGrabber\Factories\ThemesGrabCommandFactory;
use AssetGrabber\Factories\ThemesPartialCommandFactory;
use AssetGrabber\Factories\ThemesPullLatestRevCommandFactory;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\PluginListService;
use AssetGrabber\Services\PluginMetadataService;
use AssetGrabber\Services\RevisionMetadataService;
use AssetGrabber\Services\ThemeDownloadService;
use AssetGrabber\Services\ThemeListService;
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


                // Commands
                PluginListService::class       => PluginListServiceFactory::class,
                ExtendedPdoInterface::class    => ExtendedPdoFactory::class,
                PluginMetadataService::class   => PluginMetadataServiceFactory::class,
                RevisionMetadataService::class => RevisionMetadataServiceFactory::class,

                // Commands
                PluginsGrabCommand::class            => PluginsGrabCommandFactory::class,

                InternalPluginDownloadCommand::class => InternalPluginDownloadCommandFactory::class,
                PluginsPartialCommand::class         => PluginsPartialCommandFactory::class,
                PluginsMetaCommand::class            => PluginsMetaCommandFactory::class,
                PluginsImportMetaCommand::class      => PluginsImportMetaCommandFactory::class,
                UtilUploadCommand::class             => UtilUploadCommandFactory::class,

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
