<?php

declare(strict_types=1);

namespace App\Integrations\Wordpress;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DownloadRequest extends Request
{
    public function __construct(
        public readonly string $remotePath,
        public readonly string $localPath,
        public readonly string $slug,
        public readonly string $version,
    ) {}

    public function resolveEndpoint(): string
    {
        return $this->remotePath;
    }

    protected Method $method = Method::GET;
}
