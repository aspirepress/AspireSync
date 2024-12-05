<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\ResourceType;

interface SubversionServiceInterface
{
    /** @return array{slugs: array<string|int, array{}>, revision: int} */
    public function getUpdatedSlugs(ResourceType $type, int $prevRevision, int $lastRevision): array;

    /** @return array{slugs: array<string|int, array{}>, revision: int} */
    public function scrapeSlugsFromIndex(ResourceType $type): array;
}
