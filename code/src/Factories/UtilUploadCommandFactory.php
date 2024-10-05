<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\UtilUploadCommand;
use AssetGrabber\Services\Interfaces\Callback;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\Themes\ThemesMetadataService;
use Laminas\ServiceManager\ServiceManager;

class UtilUploadCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): UtilUploadCommand
    {
        $callback = new class($serviceManager) implements Callback {
            public function __construct(private ServiceManager $serviceManager)
            {
            }

            public function __invoke($action)
            {
                switch ($action) {
                    case 'plugins':
                        return $this->serviceManager->get(PluginMetadataService::class);
                    case 'themes':
                        return $this->serviceManager->get(ThemesMetadataService::class);

                    default:
                        throw new \RuntimeException('Unknown action: ' . $action);
                }
            }
        };

        $flysystem     = $serviceManager->get('util:upload');
        return new UtilUploadCommand($callback, $flysystem);
    }
}
