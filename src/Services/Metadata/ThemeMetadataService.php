<?php

declare(strict_types=1);

namespace App\Services\Metadata;

use App\ResourceType;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

readonly class ThemeMetadataService extends AbstractMetadataService
{
    public function __construct(Connection $connection, LoggerInterface $log)
    {
        parent::__construct($connection, $log, ResourceType::Theme);
    }
}
