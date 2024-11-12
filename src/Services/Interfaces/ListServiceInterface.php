<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

interface ListServiceInterface
{
    /**
     * @param  string[] $filter
     * @return array<string, string[]>
     */
    public function getItemsForAction(array $filter, ?int $min_age = null): array;

    /**
     * @param  string[] $requested
     * @return array<string, array<string>>
     */
    public function getUpdatedItems(?array $requested): array;

    public function preserveRevision(string $action): string;
}
