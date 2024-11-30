<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

interface SubversionServiceInterface
{
    /** @return array{slugs: array<string, string[]>, revision: int} */
    public function getUpdatedSlugs(string $type, int $prevRevision, int $lastRevision): array;

    /** @return array{slugs: string[], revision: int} */
    public function scrapeSlugsFromIndex(string $type): array;
}
