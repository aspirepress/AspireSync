<?php

declare(strict_types=1);

namespace AssetGrabber;

use AssetGrabber\Commands\Plugins\InternalPluginDownloadCommand;
use AssetGrabber\Commands\Plugins\PluginsGrabCommand;
use AssetGrabber\Commands\Plugins\PluginsImportMetaCommand;
use AssetGrabber\Commands\Plugins\PluginsMetaCommand;
use AssetGrabber\Commands\Plugins\PluginsPartialCommand;
use AssetGrabber\Commands\PluginsPullLatestRevCommand;
use AssetGrabber\Commands\UtilCleanDataCommand;
use AssetGrabber\Commands\UtilUploadCommand;
use AssetGrabber\Factories\ExtendedPdoFactory;
use AssetGrabber\Factories\Flysystem\AwsS3V3AdapterFactory;
use AssetGrabber\Factories\Flysystem\FilesystemFactory;
use AssetGrabber\Factories\Flysystem\LocalFilesystemAdapterFactory;
use AssetGrabber\Factories\InternalThemeDownloadCommandFactory;
use AssetGrabber\Factories\PluginDownloadServiceFactory;
use AssetGrabber\Factories\Plugins\InternalPluginDownloadCommandFactory;
use AssetGrabber\Factories\Plugins\PluginDownloadFromWpServiceFactory;
use AssetGrabber\Factories\Plugins\PluginListServiceFactory;
use AssetGrabber\Factories\Plugins\PluginMetadataServiceFactory;
use AssetGrabber\Factories\Plugins\PluginsGrabCommandFactory;
use AssetGrabber\Factories\Plugins\PluginsImportMetaCommandFactory;
use AssetGrabber\Factories\Plugins\PluginsMetaCommandFactory;
use AssetGrabber\Factories\Plugins\PluginsPartialCommandFactory;
use AssetGrabber\Factories\RevisionMetadataServiceFactory;
use AssetGrabber\Factories\ThemesGrabCommandFactory;
use AssetGrabber\Factories\ThemesPartialCommandFactory;
use AssetGrabber\Factories\ThemesPullLatestRevCommandFactory;
use AssetGrabber\Factories\UtilUploadCommandFactory;
use AssetGrabber\Services\PluginDownloadService;
use AssetGrabber\Services\Plugins\PluginDownloadFromWpService;
use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\RevisionMetadataService;
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
