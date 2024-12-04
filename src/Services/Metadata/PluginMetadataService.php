<?php

declare(strict_types=1);

namespace App\Services\Metadata;

use App\ResourceType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

readonly class PluginMetadataService extends AbstractMetadataService
{
    public function __construct(EntityManagerInterface $em, LoggerInterface $log)
    {
        parent::__construct($em, $log, ResourceType::Plugin);
    }
}
