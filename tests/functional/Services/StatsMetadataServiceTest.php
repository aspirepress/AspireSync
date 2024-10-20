<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Functional\Services;

use AspirePress\AspireSync\Services\StatsMetadataService;
use AspirePress\AspireSync\Tests\Functional\AbstractFunctionalTestBase;
use AspirePress\AspireSync\Tests\Helpers\FunctionalTestHelper;

class StatsMetadataServiceTest extends AbstractFunctionalTestBase
{
    public function testStatsAreLogged(): void
    {
        $stats   = ['a' => 0, 'b' => 1, 'c' => 2];
        $command = 'foo:bar';

        $sut = new StatsMetadataService(FunctionalTestHelper::getDb());
        $sut->logStats($command, $stats);

        $db      = FunctionalTestHelper::getDb();
        $statsDb = $db->fetchAll('SELECT * FROM stats');
        $this->assertCount(1, $statsDb);
        $statsCheck   = json_decode($statsDb[0]['stats'], true);
        $commandCheck = $statsDb[0]['command'];
        $this->assertEquals($command, $commandCheck);
        $this->assertEquals($statsCheck, $stats);
    }
}
