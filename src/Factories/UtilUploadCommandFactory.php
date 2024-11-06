<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use AspirePress\AspireSync\Commands\UtilUploadCommand;
use AspirePress\AspireSync\Services\Interfaces\CallbackInterface;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;
use RuntimeException;

class UtilUploadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): UtilUploadCommand
    {
        $callback = new class ($serviceManager) implements CallbackInterface {
            public function __construct(private ServiceManager $serviceManager)
            {
            }

            public function __invoke(string $action): object
            {
                return match ($action) {
                    'plugins' => $this->serviceManager->get(PluginMetadataService::class),
                    'themes' => $this->serviceManager->get(ThemesMetadataService::class),
                    default => throw new RuntimeException('Unknown action: ' . $action),
                };
            }
        };

        $flysystem    = $serviceManager->get('util:upload');
        $statsMeta    = $serviceManager->get(StatsMetadataService::class);
        $config       = $serviceManager->get('config');
        $uploadDriver = $config['flysystem']['util:upload'];
        $uploadType   = str_replace('upload_', '', $uploadDriver);

        return new UtilUploadCommand(
            uploadType: $uploadType,
            callback: $callback,
            flysystem: $flysystem,
            statsMetadata: $statsMeta
        );
    }
}
