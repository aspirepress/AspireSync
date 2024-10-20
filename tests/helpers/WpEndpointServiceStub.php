<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Helpers;

use AspirePress\AspireSync\Services\Interfaces\WpEndpointClientInterface;

class WpEndpointServiceStub implements WpEndpointClientInterface
{
    public function getPluginMetadata(string $plugin): string
    {
        // TODO: Implement getPlugniMetadata() method.
        return '';
    }

    public function getThemeMetadata(string $theme): string
    {
        // TODO: Implement getThemeMetadata() method.
        return '';
    }

    public function downloadFile(string $url, string $destination): string
    {
        // TODO: Implement downloadFile() method.
        return '';
    }
}
