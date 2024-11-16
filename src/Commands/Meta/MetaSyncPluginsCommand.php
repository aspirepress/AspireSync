<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Integrations\Wordpress\PluginRequest;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Plugins\PluginListService;
use AspirePress\AspireSync\Services\Plugins\PluginMetadataService;
use Saloon\Http\Request;

class MetaSyncPluginsCommand extends AbstractMetaSyncCommand
{
    public function __construct(
        PluginListService $listService,
        PluginMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($listService, $meta, $api);
    }

    protected Resource $resource = Resource::Plugin;

    protected function makeRequest($slug): Request
    {
        return new PluginRequest($slug);
    }
}
