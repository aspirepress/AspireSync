<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Themes;

use Aura\Sql\ExtendedPdoInterface;

class ThemesMetadataService
{
    public function __construct(private ExtendedPdoInterface $pdo)
    {
    }

    public function checkThemeInDatabase($them)
    {
        return false;
    }
}