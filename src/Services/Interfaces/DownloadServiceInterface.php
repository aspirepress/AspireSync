<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

interface DownloadServiceInterface
{
    /** @return array{message:string, url:string|null} */
    public function download(string $slug, string $version, bool $force = false): array;
}
