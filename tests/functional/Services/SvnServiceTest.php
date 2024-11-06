<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Functional\Services;

use AspirePress\AspireSync\Services\SvnService;
use AspirePress\AspireSync\Tests\Functional\AbstractFunctionalTestBase;
use GuzzleHttp\Client as GuzzleClient;

class SvnServiceTest extends AbstractFunctionalTestBase
{
    public function testPullingPluginsResultsInListOfPlugins(): void
    {
        $this->markTestSkipped('makes slow external network requests');
        // $sut    = new SvnService(new GuzzleClient());
        // $result = $sut->getRevisionForType('plugins', 3164521, 3164522);
        // $this->assertTrue($result['revision'] >= 3164522);
        // $this->assertTrue(count($result['items']) >= 3);
        // $item = array_shift($result['items']);
        // $this->assertIsArray($item);
    }

    public function testPullingThemesResultsInListOfThemes(): void
    {
        $this->markTestSkipped('makes slow external network requests');
        // $sut    = new SvnService(new GuzzleClient());
        // $result = $sut->getRevisionForType('themes', 244539, 244540);
        // $this->assertTrue($result['revision'] >= 244540);
        // $this->assertTrue(count($result['items']) >= 1);
        // $item = array_shift($result['items']);
        // $this->assertIsArray($item);
    }

    public function testRevisionEqualityResultsInNoSvnPull(): void
    {
        $this->markTestSkipped('makes slow external network requests');
        // $sut    = new SvnService(new GuzzleClient());
        // $result = $sut->getRevisionForType('plugins', 3164522, 3164522);
        // $this->assertSame($result['revision'], 3164522);
        // $this->assertSame(count($result['items']), 0);
    }

    public function testPullWholeListResultsInListOfPlugins(): void
    {
        $this->markTestSkipped('Test to be implemented later');
    }
}
