<?php

declare(strict_types=1);

namespace App\Integrations\Fair;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class FairConnector extends Connector
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
