<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

use AspirePress\AspireSync\Resource;

interface DotOrgApiClientInterface
{
    public function fetchMetadata(Resource $resource, string $slug): array;
}
