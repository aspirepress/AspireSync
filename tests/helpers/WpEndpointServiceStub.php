<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Helpers;

use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;

class WpEndpointServiceStub implements WpEndpointClientInterface
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
