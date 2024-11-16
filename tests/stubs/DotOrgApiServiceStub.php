<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Stubs;

use AspirePress\AspireSync\Services\Interfaces\DotOrgApiClientInterface;

class DotOrgApiServiceStub implements DotOrgApiClientInterface
{
    public function getPluginMetadata(string $slug): array
    {
        return [];
    }

    public function getThemeMetadata(string $slug): array
    {
        return [];
    }
}
