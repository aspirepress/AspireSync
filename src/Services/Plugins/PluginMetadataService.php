<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Plugins;

use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\AbstractMetadataService;
use Doctrine\DBAL\Connection;

readonly class PluginMetadataService extends AbstractMetadataService
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, Resource::Plugin);
    }
}
