<?php

declare(strict_types=1);

namespace App\Tests\Stubs;

use App\ResourceType;
use App\Services\Interfaces\SubversionServiceInterface;

class SubversionServiceStub implements SubversionServiceInterface
{
    public function getUpdatedSlugs(ResourceType $type, int $prevRevision, int $lastRevision): array
    {
        return [
            'revision' => 123,
            'slugs'    => ['foo' => [], 'bar' => [], 'baz' => []],
        ];
    }

    public function scrapeSlugsFromIndex(ResourceType $type): array
    {
        return [
            'revision' => 123,
            'slugs'    => ['foo', 'bar', 'baz'],
        ];
    }
}
