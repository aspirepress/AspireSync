<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Helpers;

use AspirePress\AspireSync\Services\Interfaces\SvnServiceInterface;

class SvnServiceStub implements SvnServiceInterface
{
    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): array
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

    public function pullWholeItemsList(string $type): array
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
