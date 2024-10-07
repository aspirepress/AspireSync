<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Interfaces;

interface ListServiceInterface
{
    /**
     * @param  array<int, string>  $filter
     * @return array<string, string[]>
     */
    public function getItemsForAction(array $filter, string $action): array;

    /**
     * @return array<string, string|int|array<string, string>>
     */
    public function getItemMetadata(string $item): array;

    /**
     * @param  array<int, string>  $explicitlyRequested
     * @return array<string, array<string>>
     */
    public function getUpdatedListOfItems(?array $explicitlyRequested): array;

    public function preserveRevision(string $action): void;
}
