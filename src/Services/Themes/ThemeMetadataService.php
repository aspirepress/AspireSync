<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\AbstractMetadataService;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

readonly class ThemeMetadataService extends AbstractMetadataService
{
    public function __construct(Connection $connection, LoggerInterface $log)
    {
        parent::__construct($connection, $log, Resource::Theme);
    }
}
