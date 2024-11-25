<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Integrations\Wordpress\ThemeRequest;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\ResourceType;
use AspirePress\AspireSync\Services\List\ThemeListService;
use AspirePress\AspireSync\Services\Metadata\ThemeMetadataService;
use Saloon\Http\Request;

class MetaSyncThemesCommand extends AbstractMetaSyncCommand
{
    public function __construct(
        ThemeListService $listService,
        ThemeMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($listService, $meta, $api, ResourceType::Theme);
    }

    protected function makeRequest(string $slug): Request
    {
        return new ThemeRequest($slug);
    }
}
