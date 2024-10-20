<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Functional\Services;

use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Tests\Functional\AbstractFunctionalTestBase;
use AspirePress\AspireSync\Tests\Helpers\FunctionalTestHelper;
use RuntimeException;

class RevisionMetadataTest extends AbstractFunctionalTestBase
{
    public function testRevisionMetadataStartsEmpty(): void
    {
        $sut          = new RevisionMetadataService(FunctionalTestHelper::getDb());
        $revisionData = $sut->getRevisionData();
        $this->assertEmpty($revisionData);
    }

    public function testSavingRevisionDataSavesData(): void
    {
        $revision = new RevisionMetadataService(FunctionalTestHelper::getDb());
        $revision->setCurrentRevision('foo:bar', 1234);
        $revision->preserveRevision('foo:bar');

        $sut      = new RevisionMetadataService(FunctionalTestHelper::getDb());
        $revision = $sut->getRevisionForAction('foo:bar');
        // TODO: Add date comparison
        $this->assertEquals(1234, $revision);
    }

    public function testPreserveRevisionWithoutRevisionInfoFails(): void
    {
        $this->expectException(RuntimeException::class);
        $sut = new RevisionMetadataService(FunctionalTestHelper::getDb());
        $sut->preserveRevision('foo:bar');
    }

    public function testSettingANewRevisionSavesTheRevision(): void
    {
        $rs = new RevisionMetadataService(FunctionalTestHelper::getDb());
        $rs->setCurrentRevision('foo:bar', 1234);
        $rs->setCurrentRevision('foo:baz', 5678);
        $rs->preserveRevision('foo:bar');
        $rs->preserveRevision('foo:baz');
        $rs = null;

        $sut      = new RevisionMetadataService(FunctionalTestHelper::getDb());
        $revision = $sut->getRevisionForAction('foo:bar');
        $this->assertEquals(1234, $revision);

        $revision = 9987;
        $sut->setCurrentRevision('foo:bar', $revision);
//.       TODO: This doesn't work because we haven't overwritten the revision, just created a new one to update.
//        $revision = $sut->getRevisionForAction('foo:bar');
//        $this->assertEquals(9987, $revision);
        $sut->preserveRevision('foo:bar');

        $sut = null;

        $sut      = new RevisionMetadataService(FunctionalTestHelper::getDb());
        $revision = $sut->getRevisionForAction('foo:bar');
        $this->assertEquals(9987, $revision);
    }
}
