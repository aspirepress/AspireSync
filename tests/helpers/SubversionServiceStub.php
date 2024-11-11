<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Helpers;

use AspirePress\AspireSync\Services\Interfaces\SubversionServiceInterface;

class SubversionServiceStub implements SubversionServiceInterface
{
    public function getUpdatedSlugs(string $type, int $prevRevision, int $lastRevision): array
    {
        return [
            'revision' => 123,
            'items'    => [
                'foo' => [],
                'bar' => [],
                'baz' => [],
            ],
        ];
    }

    public function scrapeSlugsFromIndex(string $type): array
    {
        return [
            'revision' => 123,
            'items'    => [
                'foo' => [],
                'bar' => [],
                'baz' => [],
            ],
        ];
    }
}
