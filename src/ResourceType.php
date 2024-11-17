<?php

declare(strict_types=1);

namespace AspirePress\AspireSync;

enum ResourceType: string
{
    case Plugin = 'plugin';
    case Theme = 'theme';
}
