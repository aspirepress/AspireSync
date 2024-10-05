<?php

declare(strict_types=1);

namespace AssetGrabber\Services\Themes;

use Aura\Sql\ExtendedPdoInterface;

class ThemesMetadataService
{
    private array $existing = [];

    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->existing = $this->loadExistingThemes();
    }

    /**
     * @return array|string[]
     */
    public function checkThemeInDatabase(string $slug): array
    {
        if (isset($this->existing[$slug])) {
            return $this->existing[$slug];
        }

        return [];
    }

    private function loadExistingThemes(): array
    {
        $sql = 'SELECT slug, pulled_at FROM themes';
        $result = [];
        foreach ($this->pdo->fetchAll($sql) as $row) {
            $result[$row['slug']] = ['status' => $row['status'], 'pulled_at' => $row['pulled_at']];
        }

        return $result;
    }
}