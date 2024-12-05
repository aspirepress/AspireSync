<?php

declare(strict_types=1);

namespace App\Tests\Unit\Plugin;

use App\Services\List\AbstractListService;
use App\Tests\Stubs\MetadataServiceStub;
use App\Tests\Stubs\RevisionServiceStub;
use App\Tests\Stubs\SubversionServiceStub;
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
                $rev  = new RevisionServiceStub();
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
