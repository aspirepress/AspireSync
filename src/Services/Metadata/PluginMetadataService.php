<?php

declare(strict_types=1);

namespace App\Services\Metadata;

use App\ResourceType;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

readonly class PluginMetadataService extends AbstractMetadataService
{
    public function __construct(Connection $conn, LoggerInterface $log)
    {
        parent::__construct($conn, $log, ResourceType::Plugin);
    }
}
