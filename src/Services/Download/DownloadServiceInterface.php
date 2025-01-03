<?php

declare(strict_types=1);

namespace App\Services\Download;

interface DownloadServiceInterface
{
    /** @param iterable<array{string, string}> $slugsAndVersions */
    public function downloadBatch(iterable $slugsAndVersions, bool $force = false): void;
}
