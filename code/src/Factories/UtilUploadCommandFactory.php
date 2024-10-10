<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\UtilUploadCommand;
use AssetGrabber\Services\Interfaces\CallbackInterface;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\StatsMetadataService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
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
                switch ($action) {
                    case 'plugins':
                        return $this->serviceManager->get(PluginMetadataService::class);
                    case 'themes':
                        return $this->serviceManager->get(ThemesMetadataService::class);

                    default:
                        throw new RuntimeException('Unknown action: ' . $action);
                }
            }
        };

        $flysystem    = $serviceManager->get('util:upload');
        $statsMeta    = $serviceManager->get(StatsMetadataService::class);
        $config       = $serviceManager->get('config');
        $uploadDriver = $config['flysystem']['util:upload'];
        $uploadType   = str_replace('upload_', '', $uploadDriver);

        return new UtilUploadCommand($uploadType, $callback, $flysystem, $statsMeta);
    }
}
