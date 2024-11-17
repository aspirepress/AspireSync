<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Metadata;

use AspirePress\AspireSync\ResourceType;
use AspirePress\AspireSync\Services\Metadata\AbstractMetadataService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

readonly class PluginMetadataService extends AbstractMetadataService
{
    public function __construct(Connection $connection, LoggerInterface $log)
    {
        parent::__construct($connection, $log, ResourceType::Plugin);
    }
}
