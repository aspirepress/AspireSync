<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Services\SvnService;
use Aura\Sql\ExtendedPdoInterface;
use GuzzleHttp\Client as GuzzleClient;
use Laminas\ServiceManager\ServiceManager;
use League\Flysystem\Filesystem;

class SvnServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): SvnService
    {
        $filesystem = $serviceManager->get(Filesystem::class);
        $guzzle = $serviceManager->get(GuzzleClient::class);
        return new SvnService($filesystem, $guzzle);
    }
}
