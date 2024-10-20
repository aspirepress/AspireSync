<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use AspirePress\AspireSync\Services\RevisionMetadataService;
use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

class RevisionMetadataServiceFactory
{
    public function __invoke(ServiceManager $serviceManager): RevisionMetadataService
    {
        $pdo = $serviceManager->get(ExtendedPdoInterface::class);
        return new RevisionMetadataService($pdo);
    }
}
