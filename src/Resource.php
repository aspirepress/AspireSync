<?php

declare(strict_types=1);

namespace AspirePress\AspireSync;

enum Resource: string
{
    case Plugin = 'plugin';
    case Theme = 'theme';
}
