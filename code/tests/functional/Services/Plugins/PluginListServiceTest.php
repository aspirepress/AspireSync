<?php

declare(strict_types=1);

namespace AssetGrabber\Tests\Functional\Services\Plugins;

use AssetGrabber\Services\Plugins\PluginListService;
use AssetGrabber\Services\Plugins\PluginMetadataService;
use AssetGrabber\Services\RevisionMetadataService;
use AssetGrabber\Tests\Functional\AbstractFunctionalTestBase;
use AssetGrabber\Tests\Helpers\FunctionalTestHelper;
use AssetGrabber\Tests\Helpers\SvnServiceStub;
use AssetGrabber\Tests\Helpers\WpEndpointServiceStub;

class PluginListServiceTest extends AbstractFunctionalTestBase
{
    private PluginListService $sut;
    protected function setUp(): void
    {
        $svnStub = new SvnServiceStub();
        $container = FunctionalTestHelper::getContainer();
        $pluginMetadata = $container->get(PluginMetadataService::class);
        $revisionMetadata = $container->get(RevisionMetadataService::class);
        $clientStub = new WpEndpointServiceStub();
        $this->sut = new PluginListService($svnStub, $pluginMetadata, $revisionMetadata, $clientStub);
    }

    public function testGetAllItemsWIthNoRevisionGetsAllItems()
    {
        $items = $this->sut->getItemsForAction([], 'foo');
        $this->assertEquals(['foo', 'bar', 'baz'], array_keys($items));
    }
}
