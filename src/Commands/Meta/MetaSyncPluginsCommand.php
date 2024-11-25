<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Integrations\Wordpress\PluginRequest;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\ResourceType;
use AspirePress\AspireSync\Services\List\PluginListService;
use AspirePress\AspireSync\Services\Metadata\PluginMetadataService;
use Saloon\Http\Request;

class MetaSyncPluginsCommand extends AbstractMetaSyncCommand
{
    public function __construct(
        PluginListService $listService,
        PluginMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($listService, $meta, $api, ResourceType::Plugin);
    }

    protected function makeRequest(string $slug): Request
    {
        return new PluginRequest($slug);
    }
}
