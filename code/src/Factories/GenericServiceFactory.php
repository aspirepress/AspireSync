<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use Laminas\ServiceManager\ServiceManager;

class GenericServiceFactory
{
    public function __invoke(ServiceManager $serviceManager, string $serviceName): object
    {
        $config = $serviceManager->get('config');
        $userAgents = $config['user-agents'];
        return new $serviceName($userAgents);
    }
}