<?php

declare(strict_types=1);

namespace App\Integrations\GitUpdater;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class GitUpdaterConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 10;

    public function __construct(public readonly string $baseUrl) {}

    public function resolveBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function defaultHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }
}
