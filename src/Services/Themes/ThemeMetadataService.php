<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Themes;

use AspirePress\AspireSync\Services\Interfaces\MetadataServiceInterface;
use Aura\Sql\ExtendedPdoInterface;
use PDOException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;

use function Safe\json_encode;

class ThemeMetadataService implements MetadataServiceInterface
{
    /** @var array<string, string[]> */
    private array $existing;

    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->existing = $this->loadExistingThemes();
    }

    public function getDownloadUrl(string $slug, string $version, string $type = 'wp_cdn'): string
    {
        $sql = <<<'SQL'
            SELECT file_url
            FROM sync_theme_files 
                JOIN sync_themes ON sync_themes.id = sync_theme_files.theme_id 
            WHERE sync_themes.slug = :slug 
              AND sync_theme_files.version = :version
              AND sync_theme_files.type = :type
        SQL;

        $result = $this->pdo->fetchOne($sql, ['slug' => $slug, 'type' => $type, 'version' => $version]);
        return $result['file_url'];
    }

    /**
     * @param string[] $versions
     * @return string[]
     */
    public function getUnprocessedVersions(string $theme, array $versions, string $type = 'wp_cdn'): array
    {
        $sql     = <<<'SQL'
            SELECT version 
            FROM sync_theme_files 
                LEFT JOIN sync_themes ON sync_themes.id = sync_theme_files.theme_id 
            WHERE type = :type 
              AND sync_themes.slug = :theme 
              AND processed IS NULL 
              AND sync_theme_files.version IN (:versions)
        SQL;
        $results = $this->pdo->fetchAll($sql, ['theme' => $theme, 'type' => $type, 'versions' => $versions]);
        $return  = [];
        foreach ($results as $result) {
            $return[] = $result['version'];
        }
        return $return;
    }

    /** @param array<string, mixed> $meta */
    public function saveMetadata(array $meta): void
    {
        isset($meta['error']) ? $this->saveErrorTheme($meta) : $this->saveOpenTheme($meta);
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
     * @param  array<string, string|array<string, string>>  $meta
     * @return array|string[]
     */
    public function saveOpenTheme(array $meta): void
    {
        $this->pdo->beginTransaction();

        $slug           = $meta['slug'];
        $currentVersion = $meta['version'];
        $versions       = $meta['versions'] ?: [$currentVersion => $meta['download_link']];
        $id             = Uuid::uuid7();

        $this->insertTheme([
            'id'              => $id->toString(),
            'slug'            => $slug,
            'name'            => mb_substr($meta['name'], 0, 255),
            'current_version' => $currentVersion,
            'status'          => 'open',
            'updated'         => date('c', strtotime($meta['last_updated'])),
            'pulled_at'       => date('c'),
            'metadata'        => $meta,
        ]);

        $versionResult = $this->writeVersionsForTheme($id, $versions);

        if (! empty($versionResult['error'])) {
            throw new RuntimeException('Unable to write versions for theme ' . $slug);
        }
        $this->pdo->commit();
    }

    /** @param array<string, mixed> $meta */
    public function saveErrorTheme(array $meta): void
    {
        if (! empty($meta['closed_date'])) {
            $updated = date('c', strtotime($meta['closed_date']));
        } else {
            $updated = date('c');
        }
        $this->insertTheme([
            'id'        => Uuid::uuid7()->toString(),
            'name'      => substr($meta['name'], 0, 255),
            'slug'      => $meta['slug'],
            'updated'   => $updated,
            'pulled_at' => date('c'),
            'status'    => $meta['status'] ?? 'error',
            'metadata'  => $meta,
        ]);
    }

    /**
     * @param  string[] $versions
     * @return array|string[]
     */
    public function writeVersionsForTheme(UuidInterface $themeId, array $versions, string $cdn = 'wp_cdn'): array
    {
        $sql = 'INSERT INTO sync_theme_files (id, theme_id, file_url, type, version, created) VALUES (:id, :theme_id, :file_url, :type, :version, current_timestamp)';

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
        $sql = 'UPDATE sync_theme_files SET processed = current_timestamp, hash = :hash WHERE version = :version AND type = :type AND theme_id = (SELECT id FROM sync_themes WHERE slug = :theme)';
        $this->pdo->perform($sql, ['theme' => $theme, 'type' => $type, 'hash' => $hash, 'version' => $version]);
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

    public function getPulledDateTimestamp(string $slug): ?int
    {
        $sql    = "select unixepoch(pulled_at) timestamp from sync_themes where slug = :slug";
        $result = $this->pdo->fetchOne($sql, ['slug' => $slug]);
        if (! $result) {
            return null;
        }
        return (int) $result['timestamp'];
    }

    //region Private API

    private function insertTheme(array $data): void
    {
        $now              = date('c');
        $data['metadata'] = json_encode([
            ...$data['metadata'],
            'aspirepress_meta' => [
                'seen'      => $now,
                'added'     => $now,
                'updated'   => $now,
                'processed' => null,
                'finalized' => null,
            ],
        ]);

        $sql = <<<SQL
            INSERT OR REPLACE INTO sync_themes (id, name, slug, status, updated, pulled_at, metadata) 
            VALUES (:id, :name, :slug, :status, :updated, :pulled_at, :metadata)
        SQL;
        $this->pdo->perform($sql, $data);
    }

    //endregion
}
