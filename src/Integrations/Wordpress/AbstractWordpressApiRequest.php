<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Integrations\Wordpress;

use Saloon\Enums\Method;
use Saloon\Http\Request;

abstract class AbstractWordpressApiRequest extends Request
{
    public function __construct(public readonly string $slug)
    {
    }

    protected Method $method = Method::GET;
}
