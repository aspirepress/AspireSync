<?php

declare(strict_types=1);

namespace App\Commands\Sync;

use App\Integrations\Wordpress\PluginRequest;
use App\Integrations\Wordpress\WordpressLegacyApiConnector;
use App\ResourceType;
use App\Services\Metadata\PluginMetadataService;
use Saloon\Http\Request;

class FetchPluginsCommand extends AbstractFetchCommand
{
    public function __construct(
        PluginMetadataService $meta,
        WordpressLegacyApiConnector $api,
    ) {
        parent::__construct($meta, $api, ResourceType::Plugin);
    }

    protected function makeRequest(string $slug): Request
    {
        return new PluginRequest($slug);
    }
}
