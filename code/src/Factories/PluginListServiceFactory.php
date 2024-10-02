<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Services\PluginListService;
use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

class PluginListServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginListService
    {
        $config = $serviceManager->get('config');
        $ua = $config['user-agents'];
        $pdo = $serviceManager->get(ExtendedPdoInterface::class);
        return new PluginListService($ua, $pdo);
    }
}
