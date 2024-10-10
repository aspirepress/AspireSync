<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Functional\Services\Plugins;

use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use AspirePress\AspireSync\Services\RevisionMetadataService;
use AspirePress\AspireSync\Tests\Functional\AbstractFunctionalTestBase;
use AspirePress\AspireSync\Tests\Helpers\FunctionalTestHelper;
use AspirePress\AspireSync\Tests\Helpers\SvnServiceStub;
use AspirePress\AspireSync\Tests\Helpers\WpEndpointServiceStub;

class PluginListServiceTest extends AbstractFunctionalTestBase
{
    private PluginListService $sut;
    protected function setUp(): void
    {
        $svnStub          = new SvnServiceStub();
        $container        = FunctionalTestHelper::getContainer();
        $pluginMetadata   = $container->get(PluginMetadataService::class);
        $revisionMetadata = $container->get(RevisionMetadataService::class);
        $clientStub       = new WpEndpointServiceStub();
        $this->sut        = new PluginListService($svnStub, $pluginMetadata, $revisionMetadata, $clientStub);
    }

    public function testGetAllItemsWIthNoRevisionGetsAllItems(): void
    {
        $items = $this->sut->getItemsForAction([], 'foo');
        $this->assertEquals(['foo', 'bar', 'baz'], array_keys($items));
    }
}
