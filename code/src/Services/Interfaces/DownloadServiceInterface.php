<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Interfaces;

interface DownloadServiceInterface
{
    /**
     * @param string[] $versions
     * @return array<string, string[]>
     */
    public function download(string $plugin, array $versions, string $numToDownload = 'all', bool $force = false): array;
}
