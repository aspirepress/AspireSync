<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

interface WpEndpointClientInterface
{
    public function getPluginMetadata(string $slug): array;

    public function getThemeMetadata(string $theme): string;

    public function downloadFile(string $url, string $destination): string;
}
