<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Download;

interface DownloadServiceInterface
{
    public function downloadBatch(iterable $slugsAndVersions, bool $force = false): void;
}
