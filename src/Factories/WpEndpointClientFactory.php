<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;
use AspirePress\AspireSync\Services\WPEndpointClient;
use GuzzleHttp\Client as GuzzleClient;
use Laminas\ServiceManager\ServiceManager;

class WpEndpointClientFactory
{
    public function __invoke(ServiceManager $serviceManager): WpEndpointClientInterface
    {
        $guzzle = $serviceManager->get(GuzzleClient::class);
        return new WPEndpointClient($guzzle);
    }
}
