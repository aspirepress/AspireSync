<?php

declare(strict_types=1);

namespace App\Commands\Sync;

use App\Integrations\Wordpress\ThemeRequest;
use App\Integrations\Wordpress\WordpressLegacyApiConnector;
use App\ResourceType;
use App\Services\Metadata\ThemeMetadataService;
use Saloon\Http\Request;

class FetchThemesCommand extends AbstractFetchCommand
{
    public function __construct(
        ThemeMetadataService $meta,
        WordpressLegacyApiConnector $api,
    ) {
        parent::__construct($meta, $api, ResourceType::Theme);
    }

    protected function makeRequest(string $slug): Request
    {
        return new ThemeRequest($slug);
    }
}
