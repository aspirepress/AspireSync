<?php

declare(strict_types=1);

namespace App\Commands\Sync\Meta;

use App\Integrations\Wordpress\ThemeRequest;
use App\Integrations\Wordpress\WordpressApiConnector;
use App\ResourceType;
use App\Services\Metadata\ThemeMetadataService;
use Saloon\Http\Request;

class MetaFetchThemesCommand extends AbstractMetaFetchCommand
{
    public function __construct(
        ThemeMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($meta, $api, ResourceType::Theme);
    }

    protected function makeRequest(string $slug): Request
    {
        return new ThemeRequest($slug);
    }
}
