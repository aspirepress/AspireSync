<?php

declare(strict_types=1);

namespace App\Commands\Sync\Meta;

use App\Integrations\Wordpress\PluginRequest;
use App\Integrations\Wordpress\WordpressApiConnector;
use App\ResourceType;
use App\Services\List\PluginListService;
use App\Services\Metadata\PluginMetadataService;
use Saloon\Http\Request;

class MetaFetchPluginsCommand extends AbstractMetaFetchCommand
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
