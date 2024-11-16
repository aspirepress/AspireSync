<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Commands\Meta;

use AspirePress\AspireSync\Integrations\Wordpress\ThemeRequest;
use AspirePress\AspireSync\Integrations\Wordpress\WordpressApiConnector;
use AspirePress\AspireSync\Resource;
use AspirePress\AspireSync\Services\Themes\ThemeListService;
use AspirePress\AspireSync\Services\Themes\ThemeMetadataService;
use Saloon\Http\Request;

class MetaSyncThemesCommand extends AbstractMetaSyncCommand
{
    public function __construct(
        ThemeListService $listService,
        ThemeMetadataService $meta,
        WordpressApiConnector $api,
    ) {
        parent::__construct($listService, $meta, $api, Resource::Theme);
    }

    protected function makeRequest($slug): Request
    {
        return new ThemeRequest($slug);
    }
}
