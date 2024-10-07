<?php

declare(strict_types=1);

namespace AssetGrabber\Tests\Functional\Services;

use AssetGrabber\Services\StatsMetadataService;
use AssetGrabber\Tests\Functional\AbstractFunctionalTestBase;
use AssetGrabber\Tests\Helpers\FunctionalTestHelper;

class StatsMetadataServiceTest extends AbstractFunctionalTestBase
{
    public function testStatsAreLogged()
    {
        $stats = ['a' => 0, 'b' => 1, 'c'=> 2];
        $command = 'foo:bar';

        $sut = new StatsMetadataService(FunctionalTestHelper::getDb());
        $sut->logStats($command, $stats);

        $db = FunctionalTestHelper::getDb();
        $statsDb = $db->fetchAll('SELECT * FROM stats');
        $this->assertCount(1, $statsDb);
        $statsCheck = json_decode($statsDb[0]['stats'], true);
        $commandCheck = $statsDb[0]['command'];
        $this->assertEquals($command, $commandCheck);
        $this->assertEquals($statsCheck, $stats);
    }
}