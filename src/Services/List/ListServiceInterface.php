<?php

declare(strict_types=1);

namespace App\Services\List;

interface ListServiceInterface
{
    /**
     * @param  string[] $filter
     * @return array<string, string[]>
     */
    public function getItems(?array $filter, ?int $min_age = null): array;

    /**
     * @param  string[] $requested
     * @return array<string, array<string>>
     */
    public function getUpdatedItems(?array $requested): array;

    public function preserveRevision(): string;
}
