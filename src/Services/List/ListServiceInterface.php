<?php

declare(strict_types=1);

namespace App\Services\List;

interface ListServiceInterface
{
    public function setName(string $name): void;

    /** @return array<string, string[]> */
    public function getItems(): array;

    /** @return array<string, array<string>> */
    public function getUpdatedItems(): array;

    public function preserveRevision(): string;
}
