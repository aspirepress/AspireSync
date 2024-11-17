<?php

declare(strict_types=1);

namespace AspirePress\AspireSync;

enum ResourceType: string
{
    case Plugin = 'plugin';
    case Theme = 'theme';

    public function plural(): string
    {
        return $this->value . 's';  // we can get away with this since we have no irregular forms yet
    }

    // Most things just use ->value directly, this method just exists to complement plural()
    public function singular(): string
    {
        return $this->value;
    }
}
