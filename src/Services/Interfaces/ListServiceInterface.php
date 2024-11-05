<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

interface ListServiceInterface
{
    /**
     * @param  array<int, string>  $filter
     * @return array<string, string[]>
     */
    public function getItemsForAction(array $filter, string $action): array;

    /**
     * @param  array<int, string>  $explicitlyRequested
     * @return array<string, array<string>>
     */
    public function getUpdatedListOfItems(?array $explicitlyRequested): array;

    public function preserveRevision(string $action): string;
}
