<?php

declare(strict_types=1);

namespace AspirePress\AspireSync;

use AspirePress\AspireSync\Commands\Plugins\DownloadPluginsCommand;
use AspirePress\AspireSync\Commands\Plugins\DownloadPluginsPartialCommand;
use AspirePress\AspireSync\Commands\Plugins\InternalPluginDownloadCommand;
use AspirePress\AspireSync\Commands\Plugins\MetaDownloadPluginsCommand;
use AspirePress\AspireSync\Commands\RunAllCommand;
use AspirePress\AspireSync\Commands\Themes\DownloadThemesCommand;
use AspirePress\AspireSync\Commands\Themes\DownloadThemesPartialCommand;
use AspirePress\AspireSync\Commands\Themes\InternalThemeDownloadCommand;
use AspirePress\AspireSync\Commands\Themes\MetaDownloadThemesCommand;
use AspirePress\AspireSync\Commands\UtilCleanCommand;
use AspirePress\AspireSync\Commands\UtilUploadCommand;
use AspirePress\AspireSync\Factories\ExtendedPdoFactory;
use AspirePress\AspireSync\Factories\Flysystem\AwsS3V3AdapterFactory;
use AspirePress\AspireSync\Factories\Flysystem\FilesystemFactory;
use AspirePress\AspireSync\Factories\Flysystem\LocalFilesystemAdapterFactory;
use AspirePress\AspireSync\Factories\GuzzleClientFactory;
use AspirePress\AspireSync\Factories\Plugins\DownloadPluginsCommandFactory;
use AspirePress\AspireSync\Factories\Plugins\DownloadPluginsPartialCommandFactory;
use AspirePress\AspireSync\Factories\Plugins\InternalPluginDownloadCommandFactory;
use AspirePress\AspireSync\Factories\Plugins\MetaDownloadPluginsCommandFactory;
use AspirePress\AspireSync\Factories\Plugins\PluginDownloadFromWpServiceFactory;
use AspirePress\AspireSync\Factories\Plugins\PluginListServiceFactory;
use AspirePress\AspireSync\Factories\Plugins\PluginMetadataServiceFactory;
use AspirePress\AspireSync\Factories\RevisionMetadataServiceFactory;
use AspirePress\AspireSync\Factories\StatsMetadataServiceFactory;
use AspirePress\AspireSync\Factories\SvnServiceFactory;
use AspirePress\AspireSync\Factories\Themes\DownloadThemesCommandFactory;
use AspirePress\AspireSync\Factories\Themes\DownloadThemesPartialCommandFactory;
use AspirePress\AspireSync\Factories\Themes\InternalThemeDownloadCommandFactory;
use AspirePress\AspireSync\Factories\Themes\MetaDownloadThemesCommandFactory;
use AspirePress\AspireSync\Factories\Themes\ThemeDownloadFromWpServiceFactory;
use AspirePress\AspireSync\Factories\Themes\ThemeListServiceFactory;
use AspirePress\AspireSync\Factories\Themes\ThemeMetadataServiceFactory;
use AspirePress\AspireSync\Factories\UtilUploadCommandFactory;
use AspirePress\AspireSync\Factories\WpEndpointClientFactory;
use AspirePress\AspireSync\Services\Plugins\PluginDownloadFromWpService;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\SvnService;
use AspirePress\AspireSync\Services\Themes\ThemeDownloadFromWpService;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use AspirePress\AspireSync\Services\WPEndpointClient;
use Aura\Sql\ExtendedPdoInterface;
use GuzzleHttp\Client as GuzzleClient;
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
                UtilCleanCommand::class => UtilCleanCommand::class,
                SvnService::class       => SvnService::class,
                WPEndpointClient::class => WPEndpointClient::class,
                RunAllCommand::class    => RunAllCommand::class,
            ],
            'factories'  => [
                // Metadata Services
                RevisionMetadataService::class => RevisionMetadataServiceFactory::class,
                ThemesMetadataService::class   => ThemeMetadataServiceFactory::class,
                PluginMetadataService::class   => PluginMetadataServiceFactory::class,
                StatsMetadataService::class    => StatsMetadataServiceFactory::class,
                SvnService::class              => SvnServiceFactory::class,

                // Services
                PluginDownloadFromWpService::class => PluginDownloadFromWpServiceFactory::class,
                ThemeListService::class            => ThemeListServiceFactory::class,
                PluginListService::class           => PluginListServiceFactory::class,
                ThemeDownloadFromWpService::class  => ThemeDownloadFromWpServiceFactory::class,
                GuzzleClient::class                => GuzzleClientFactory::class,
                WPEndpointClient::class            => WpEndpointClientFactory::class,

                // Commands
                DownloadPluginsCommand::class        => DownloadPluginsCommandFactory::class,
                InternalPluginDownloadCommand::class => InternalPluginDownloadCommandFactory::class,
                DownloadPluginsPartialCommand::class => DownloadPluginsPartialCommandFactory::class,
                MetaDownloadPluginsCommand::class    => MetaDownloadPluginsCommandFactory::class,
                UtilUploadCommand::class             => UtilUploadCommandFactory::class,
                MetaDownloadThemesCommand::class     => MetaDownloadThemesCommandFactory::class,
                DownloadThemesCommand::class         => DownloadThemesCommandFactory::class,
                InternalThemeDownloadCommand::class  => InternalThemeDownloadCommandFactory::class,
                DownloadThemesPartialCommand::class  => DownloadThemesPartialCommandFactory::class,

                // Database Services
                ExtendedPdoInterface::class => ExtendedPdoFactory::class,

                // Flysystem
                Filesystem::class             => FilesystemFactory::class,
                LocalFilesystemAdapter::class => LocalFilesystemAdapterFactory::class,
                AwsS3V3Adapter::class         => AwsS3V3AdapterFactory::class,

                // Util Flysystem Config
                'util:upload' => FilesystemFactory::class,

                // Upload Configuration Aliases
                'upload_local_filesystem' => LocalFilesystemAdapterFactory::class,
                'upload_aws_s3'           => AwsS3V3AdapterFactory::class,
            ],
        ];
    }
}
