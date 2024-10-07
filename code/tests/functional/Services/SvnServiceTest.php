<?php

declare(strict_types=1);

namespace AssetGrabber\Tests\Functional\Services;

use AssetGrabber\Services\SvnService;
use AssetGrabber\Tests\Functional\AbstractFunctionalTestBase;

class SvnServiceTest extends AbstractFunctionalTestBase
{
    public function testPullingPluginsResultsInListOfPlugins(): void
    {
        $sut    = new SvnService();
        $result = $sut->getRevisionForType('plugins', 3164521, 3164522);
        $this->assertTrue($result['revision'] >= 3164522);
        $this->assertTrue(count($result['items']) >= 3);
        $item = array_shift($result['items']);
        $this->assertIsArray($item);
    }

    public function testPullingThemesResultsInListOfThemes(): void
    {
        $sut    = new SvnService();
        $result = $sut->getRevisionForType('themes', 244539, 244540);
        $this->assertTrue($result['revision'] >= 244540);
        $this->assertTrue(count($result['items']) >= 1);
        $item = array_shift($result['items']);
        $this->assertIsArray($item);
    }

    public function testRevisionEqualityResultsInNoSvnPull(): void
    {
        $sut    = new SvnService();
        $result = $sut->getRevisionForType('plugins', 3164522, 3164522);
        $this->assertTrue($result['revision'] === 3164522);
        $this->assertTrue(count($result['items']) === 0);
    }

    public function testPullWholeListResultsInListOfPlugins(): void
    {
        $this->markTestSkipped('Test to be implemented later');
    }
}
