<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Unit\Plugin;

use AspirePress\AspireSync\Services\AbstractListService;
use AspirePress\AspireSync\Tests\Stubs\MetadataServiceStub;
use AspirePress\AspireSync\Tests\Stubs\RevisionMetadataServiceStub;
use AspirePress\AspireSync\Tests\Stubs\SubversionServiceStub;
use PHPUnit\Framework\TestCase;

class AbstractListServiceTest extends TestCase
{
    private AbstractListService $sut;

    protected function setUp(): void
    {
        $this->sut = new readonly class extends AbstractListService {
            public function __construct()
            {
                $svn  = new SubversionServiceStub();
                $meta = new MetadataServiceStub();
                $rev  = new RevisionMetadataServiceStub();
                parent::__construct($svn, $meta, $rev, 'stuff');
            }
        };
    }

    public function testStuff(): void
    {
        $items = $this->sut->getItems([]);
        $this->assertEquals(['foo', 'bar', 'baz'], array_keys($items));
    }
}
