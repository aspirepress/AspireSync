<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use AspirePress\AspireSync\Services\SvnService;
use GuzzleHttp\Client as GuzzleClient;
use Laminas\ServiceManager\ServiceManager;

class SvnServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): SvnService
    {
        return new SvnService($serviceManager->get(GuzzleClient::class));
    }
}
