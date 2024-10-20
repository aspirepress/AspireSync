<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

interface DownloadServiceInterface
{
    /**
     * @param string[] $versions
     * @return array<string, string[]>
     */
    public function download(string $theme, array $versions, string $numToDownload = 'all', bool $force = false): array;
}
