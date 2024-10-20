<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

interface SvnServiceInterface
{
    /**
     * @return array{revision: int, items: array<string, array<int, string>>}
     */
    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): array;

    /**
     * @return array{revision: int, items: array<string, array<int, string>>}
     */
    public function pullWholeItemsList(string $type): array;
}
