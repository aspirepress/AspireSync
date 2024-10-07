<?php

declare(strict_types=1);

namespace AssetGrabber\Tests\Helpers;

use AssetGrabber\Services\Interfaces\SvnServiceInterface;
use SimpleXMLElement;

class SvnServiceStub implements SvnServiceInterface
{
    public function getRevisionForType(string $type, int $prevRevision, int $lastRevision): array
    {
        // TODO: Implement getRevisionForType() method.
    }

    public function pullWholeItemsList(string $type): array
    {
        return ['revision' => 123,
            'items' => [
                'foo' => [],
                'bar' => [],
                'baz' => [],
                ]
            ];
    }
}