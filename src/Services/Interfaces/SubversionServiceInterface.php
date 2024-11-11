<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

interface SubversionServiceInterface
{
    /**
     * @return array{revision: int, items: array<string, array<int, string>>}
     */
    public function getUpdatedSlugs(string $type, int $prevRevision, int $lastRevision): array;

    /**
     * @return array{revision: int, items: array<string, array<int, string>>}
     */
    public function scrapeSlugsFromIndex(string $type): array;
}
