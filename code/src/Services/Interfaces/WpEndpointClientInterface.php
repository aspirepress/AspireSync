<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Interfaces;

interface WpEndpointClientInterface
{
    public function getPlugniMetadata(string $plugin): string;

    public function getThemeMetadata(string $theme): string;

    public function downloadFile(string $url, string $destination): string;
}
