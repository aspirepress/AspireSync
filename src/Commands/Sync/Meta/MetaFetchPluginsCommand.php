<?php

declare(strict_types=1);

namespace App\Commands\Sync\Meta;

use App\Integrations\Wordpress\PluginRequest;
use App\Integrations\Wordpress\WordpressApiConnector;
use App\ResourceType;
use App\Services\Metadata\PluginMetadataService;
use Saloon\Http\Request;

class MetaFetchPluginsCommand extends AbstractMetaFetchCommand
{
    public function __construct(
        PluginMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($meta, $api, ResourceType::Plugin);
    }

    protected function makeRequest(string $slug): Request
    {
        return new PluginRequest($slug);
    }
}
