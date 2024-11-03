<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use Aura\Sql\ExtendedPdoInterface;
use Exception;
use PDOException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

use function Safe\json_decode;
use function Safe\json_encode;

class ThemesMetadataService
{
    /** @var array<string, string[]> */
    private array $existing;

    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->existing = $this->loadExistingThemes();
    }

    /**
     * @return array|string[]
     */
    public function checkThemeInDatabase(string $slug): array
    {
        return $this->existing[$slug] ?? [];
    }

    /**
     * @return array<string, string[]>
     */
    private function loadExistingThemes(): array
    {
        $sql    = 'SELECT slug, pulled_at FROM sync_themes';
        $result = [];
        foreach ($this->pdo->fetchAll($sql) as $row) {
            $result[$row['slug']] = ['pulled_at' => $row['pulled_at']];
        }

        return $result;
    }

    /**
     * @param  array<string, string|array<string, string>>  $fileContents
     * @return array|string[]
     */
    public function updateThemeFromWP(array $fileContents, string $pulledAt): array
    {
        return $this->updateTheme($fileContents, $pulledAt);
    }

    /**
     * @param  array<string, string|array<string, string>>  $themeMetadata
     * @return array|string[]
     */
    public function saveThemeFromWP(array $themeMetadata, string $pulledAt): array
    {
        $this->pdo->beginTransaction();

        try {
            $name           = substr($themeMetadata['name'], 0, 255);
            $slug           = $themeMetadata['slug'];
            $currentVersion = $themeMetadata['version'];
            $versions       = $themeMetadata['versions'];
            $updatedAt      = date('c', strtotime($themeMetadata['last_updated']));
            $id             = Uuid::uuid7();

            $themeMetadata['aspirepress_meta'] = [
                'seen'      => date('c'),
                'added'     => date('c'),
                'updated'   => date('c'),
                'processed' => null,
                'finalized' => null,
            ];

            $sql = 'INSERT INTO sync_themes (id, name, slug, current_version, updated, pulled_at, metadata) VALUES (:id, :name, :slug, :current_version, :updated_at, :pulled_at, :metadata)';
            $this->pdo->perform($sql, [
                'id'              => $id->toString(),
                'name'            => $name,
                'slug'            => $slug,
                'current_version' => $currentVersion,
                'updated_at'      => $updatedAt,
                'pulled_at'       => $pulledAt,
                'metadata'        => json_encode($themeMetadata),
            ]);

            if (empty($themeMetadata['versions'])) {
                $versions[$themeMetadata['version']] = $themeMetadata['download_link'];
            }

            $versionResult = $this->writeVersionsForTheme($id, $versions, 'wp_cdn');

            if (! empty($versionResult['error'])) {
                throw new Exception('Unable to write versions for theme ' . $slug);
            }
            $this->pdo->commit();
            return ['error' => ''];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, string|array<string, string>>  $fileContents
     * @return string[]
     */
    private function updateTheme(array $fileContents, string $pulledAt): array
    {
        $this->pdo->beginTransaction();

        try {
            $mdSql      = 'SELECT id, metadata FROM sync_themes WHERE slug = :slug';
            $result     = $this->pdo->fetchOne($mdSql, ['slug' => $fileContents['slug']]);
            $metadata   = json_decode($result['metadata'], true);
            $id         = Uuid::fromString($result['id']);
            $apMetadata = $metadata['aspirepress_meta'];

            $newMetadata                     = $fileContents;
            $newMetadata['aspirepress_meta'] = [
                'seen'      => $apMetadata['seen'],
                'added'     => $apMetadata['added'],
                'updated'   => date('c'),
                'processed' => null,
                'finalized' => null,
            ];

            $name           = substr($fileContents['name'], 0, 255);
            $slug           = $fileContents['slug'];
            $currentVersion = $fileContents['version'];
            $versions       = $fileContents['versions'];
            $updatedAt      = date('c', strtotime($fileContents['last_updated']));

            $sql = 'UPDATE sync_themes SET metadata = :metadata, name = :name, current_version = :current_version, updated = :updated, pulled_at = :pulled_at WHERE slug = :slug';
            $this->pdo->perform($sql, [
                'name'            => $name,
                'slug'            => $slug,
                'current_version' => $currentVersion,
                'updated'         => $updatedAt,
                'pulled_at'       => $pulledAt,
                'metadata'        => json_encode($newMetadata),
            ]);

            if (empty($fileContents['versions'])) {
                $versions = [$fileContents['version'] => $fileContents['download_link']];
            }

            $newVersions = $this->getNewlyDiscoveredVersionsList($id, $versions);

            $versionResult = $this->writeVersionsForTheme($id, $newVersions, 'wp_cdn');

            if (! empty($versionResult['error'])) {
                throw new Exception('Unable to write versions for theme ' . $slug);
            }

            $this->pdo->commit();
            return ['error' => ''];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, string[]>  $versions
     * @return array|string[]
     */
    public function writeVersionProcessed(UuidInterface $themeId, array $versions, string $hash, string $cdn = 'wp_cdn'): array
    {
        $sql = 'INSERT INTO sync_theme_files (id, theme_id, file_url, type, version, created, processed, hash) VALUES (:id, :theme_id, :file_url, :type, :version, NOW(), NOW(), :hash)';

        if (! $this->pdo->inTransaction()) {
            $ourTransaction = true;
            $this->pdo->beginTransaction();
        }

        try {
            foreach ($versions as $version => $url) {
                $this->pdo->perform($sql, [
                    'id'       => Uuid::uuid7()->toString(),
                    'theme_id' => $themeId->toString(),
                    'file_url' => $url,
                    'type'     => $cdn,
                    'version'  => $version,
                    'hash'     => $hash,
                ]);
            }

            if (isset($ourTransaction)) {
                $this->pdo->commit();
            }

            return ['error' => ''];
        } catch (PDOException $e) {
            if (isset($ourTransaction)) {
                $this->pdo->rollBack();
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  string[] $versions
     * @return array|string[]
     */
    public function writeVersionsForTheme(UuidInterface $themeId, array $versions, string $cdn = 'wp_cdn'): array
    {
        $sql = 'INSERT INTO sync_theme_files (id, theme_id, file_url, type, version, created) VALUES (:id, :theme_id, :file_url, :type, :version, NOW())';

        if (! $this->pdo->inTransaction()) {
            $ourTransaction = true;
            $this->pdo->beginTransaction();
        }

        try {
            foreach ($versions as $version => $url) {
                $this->pdo->perform($sql, [
                    'id'       => Uuid::uuid7()->toString(),
                    'theme_id' => $themeId->toString(),
                    'file_url' => $url,
                    'type'     => $cdn,
                    'version'  => $version,
                ]);
            }

            if (isset($ourTransaction)) {
                $this->pdo->commit();
            }

            return ['error' => ''];
        } catch (PDOException $e) {
            if (isset($ourTransaction)) {
                $this->pdo->rollBack();
            }
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  string[]  $versions
     * @return string[]
     */
    private function getNewlyDiscoveredVersionsList(UuidInterface $id, array $versions): array
    {
        $existingVersions = "SELECT version FROM sync_theme_files WHERE type = 'wp_cdn' AND theme_id = :id";
        $existingVersions = $this->pdo->fetchCol($existingVersions, ['id' => $id->toString()]);

        $newVersions = [];
        foreach ($versions as $version => $url) {
            if (! in_array($version, $existingVersions, true)) {
                $newVersions[$version] = $url;
            }
        }

        return $newVersions;
    }

    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $theme, array $versions, string $type = 'wp_cdn'): array
    {
        $sql     = 'SELECT version FROM sync_theme_files LEFT JOIN sync_themes ON sync_themes.id = sync_theme_files.theme_id WHERE type = :type AND sync_themes.slug = :theme AND processed IS NULL AND sync_theme_files.version IN (:versions)';
        $results = $this->pdo->fetchAll($sql, ['theme' => $theme, 'type' => $type, 'versions' => $versions]);
        $return  = [];
        foreach ($results as $result) {
            $return[] = $result['version'];
        }
        return $return;
    }

    /**
     * @param array<int, string> $versions
     * @return array<string, string>
     */
    public function getDownloadUrlsForVersions(string $theme, array $versions, string $type = 'wp_cdn'): array
    {
        try {
            $sql = 'SELECT version, file_url FROM sync_theme_files LEFT JOIN sync_themes ON sync_themes.id = sync_theme_files.theme_id WHERE sync_themes.slug = :theme AND sync_theme_files.type = :type AND version IN (:versions)';

            $results = $this->pdo->fetchAll($sql, ['theme' => $theme, 'type' => $type, 'versions' => $versions]);
            $return  = [];
            foreach ($results as $result) {
                $return[$result['version']] = $result['file_url'];
            }
            return $return;
        } catch (PDOException $e) {
            throw new RuntimeException('Unable to get download URLs for theme ' . $theme . '; reason: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, string[]>
     */
    public function getVersionsForUnfinalizedThemes(?string $revDate, string $type = 'wp_cdn'): array
    {
        $notFound = $this->getNotFoundThemes();

        try {
            $sql  = "SELECT sync_themes.id, slug, version FROM sync_theme_files LEFT JOIN sync_themes ON sync_themes.id = sync_theme_files.theme_id WHERE sync_theme_files.type = :type";
            $args = ['type' => $type];
            if ($revDate) {
                $sql            .= ' AND themes.pulled_at >= :revDate';
                $args['revDate'] = $revDate;
            }
            $result      = $this->pdo->fetchAll($sql, $args);
            $finalResult = [];
            foreach ($result as $row) {
                $theme   = $row['slug'];
                $version = $row['version'];
                if (! in_array($version, $notFound, true)) {
                    $finalResult[$theme][] = $version;
                }
            }
            return $finalResult;
        } catch (PDOException $e) {
            throw new RuntimeException('Unable to get versions for themes; reason: ' . $e->getMessage());
        }
    }

    public function setVersionToDownloaded(string $theme, string $version, ?string $hash = null, string $type = 'wp_cdn'): void
    {
        $sql = 'UPDATE sync_theme_files SET processed = NOW(), hash = :hash WHERE version = :version AND type = :type AND theme_id = (SELECT id FROM sync_themes WHERE slug = :theme)';
        $this->pdo->perform($sql, ['theme' => $theme, 'type' => $type, 'hash' => $hash, 'version' => $version]);
    }

    /**
     * @return string[]
     */
    public function getVersionData(string $themeId, ?string $version, string $type = 'wp_cdn'): array|bool
    {
        $sql  = 'SELECT * FROM sync_theme_files WHERE theme_id = :theme_id AND type = :type';
        $args = [
            'theme_id' => $themeId,
            'type'     => $type,
        ];
        if ($version) {
            $sql            .= ' AND version = :version';
            $args['version'] = $version;
        }

        return $this->pdo->fetchOne($sql, $args);
    }

    /**
     * @param array<int, string> $filterBy
     * @return string[]
     */
    public function getData(array $filterBy = []): array
    {
        if (! empty($filterBy)) {
            $sql    = "SELECT id, slug FROM sync_themes WHERE slug IN (:themes)";
            $themes = $this->pdo->fetchAll($sql, ['themes' => $filterBy]);
        } else {
            $sql    = "SELECT id, slug FROM sync_themes";
            $themes = $this->pdo->fetchAll($sql);
        }
        $result = [];
        foreach ($themes as $theme) {
            $result[$theme['slug']] = $theme['id'];
        }

        return $result;
    }

    /**
     * @return array<int, string>
     */
    public function getNotFoundThemes(): array
    {
        $sql = "SELECT item_slug FROM sync_not_found_items WHERE item_type = 'theme'";
        return $this->pdo->fetchAll($sql);
    }

    public function isNotFound(string $item, bool $noLimit = false): bool
    {
        $sql = "SELECT COUNT(*) FROM sync_not_found_items WHERE item_slug = :item AND item_type = 'theme'";

        if (! $noLimit) {
            $sql .= " AND created_at > NOW() - INTERVAL '1 WEEK'";
        }

        $result = $this->pdo->fetchOne($sql, ['item' => $item]);
        return $result['count'] > 0;
    }

    public function markItemNotFound(string $item): void
    {
        if ($this->isNotFound($item, true)) {
            $sql = "UPDATE sync_not_found_items SET updated_at = NOW() WHERE item_slug = :item AND item_type = 'theme'";
            $this->pdo->perform($sql, ['item' => $item]);
        } else {
            $sql = "INSERT INTO sync_not_found_items (id, item_type, item_slug) VALUES (:id, 'theme', :item)";
            $this->pdo->perform($sql, ['id' => Uuid::uuid7()->toString(), 'item' => $item]);
        }
    }

    public function getStorageDir(): string
    {
        return '/opt/aspiresync/data/themes';
    }

    public function getS3Path(): string
    {
        return '/themes/';
    }

    public function getHashForId(string $themeId, string $version): string
    {
        $sql       = "SELECT hash FROM sync_theme_files WHERE theme_id = :item_id AND version = :version AND type = 'wp_cdn'";
        $hashArray = $this->pdo->fetchOne($sql, ['item_id' => $themeId, 'version' => $version]);
        return $hashArray['hash'] ?? '';
    }
}
