<?php

declare(strict_types=1);

namespace App\Integrations\GitUpdater;

class UpdateApiRequest extends AbstractGitUpdaterRequest
{
    public function __construct(public readonly string $slug) {}

    public function resolveEndpoint(): string
    {
        return '/update-api';
    }

    public function defaultQuery(): array
    {
        return ['slug' => $this->slug,];
    }

}
