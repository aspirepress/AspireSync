<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use Aura\Sql\ExtendedPdoInterface;
use Exception;
use PDOException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class PluginMetadataService
{
    /** @var string[][]  */
    private array $existing;
    public function __construct(private ExtendedPdoInterface $pdo)
    {
        $this->existing = $this->loadExistingPlugins();
    }

    /**
     * @param  array<string, string|array<string, string>>  $pluginMetadata
     * @return array|string[]
     */
    public function saveClosedPluginFromWP(array $pluginMetadata, string $pulledAt): array
    {
        $sql = "INSERT INTO plugins (id, name, slug, status, updated, pulled_at) VALUES (:id, :name, :slug, :status, :closed_date, :pulled_at)";

        if (! empty($pluginMetadata['closed_date'])) {
            $closedDate = date('c', strtotime($pluginMetadata['closed_date']));
        } else {
            $closedDate = date('c');
        }

        $this->pdo->beginTransaction();

        try {
            $this->pdo->perform($sql, [
                'id'          => Uuid::uuid7()->toString(),
                'name'        => substr($pluginMetadata['name'], 0, 255),
                'slug'        => $pluginMetadata['slug'],
                'closed_date' => $closedDate,
                'pulled_at'   => $pulledAt,
                'status'      => $pluginMetadata['error'],
            ]);
            $this->pdo->commit();
            return ['error' => ''];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, string|array<string, string>>  $pluginMetadata
     * @return array|string[]
     */
    public function saveOpenPluginFromWP(array $pluginMetadata, string $pulledAt): array
    {
        $this->pdo->beginTransaction();

        try {
            $name           = substr($pluginMetadata['name'], 0, 255);
            $slug           = $pluginMetadata['slug'];
            $currentVersion = $pluginMetadata['version'];
            $versions       = $pluginMetadata['versions'];
            $updatedAt      = date('c', strtotime($pluginMetadata['last_updated']));
            $id             = Uuid::uuid7();

            $sql = 'INSERT INTO plugins (id, name, slug, current_version, status, updated, pulled_at) VALUES (:id, :name, :slug, :current_version, :status, :updated_at, :pulled_at)';
            $this->pdo->perform($sql, [
                'id'              => $id->toString(),
                'name'            => $name,
                'slug'            => $slug,
                'current_version' => $currentVersion,
                'status'          => 'open',
                'updated_at'      => $updatedAt,
                'pulled_at'       => $pulledAt,
            ]);

            if (empty($pluginMetadata['versions'])) {
                $versions[$pluginMetadata['version']] = $pluginMetadata['download_link'];
            } else {
                $versions = $pluginMetadata['versions'];
            }

            $versionResult = $this->writeVersionsForPlugin($id, $versions, 'wp_cdn');

            if (! empty($versionResult['error'])) {
                throw new Exception('Unable to write versions for plugin ' . $slug);
            }
            $this->pdo->commit();
            return ['error' => ''];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  string[]  $versions
     * @return array|string[]
     */
    public function writeVersionsForPlugin(UuidInterface $pluginId, array $versions, string $cdn): array
    {
        $sql = 'INSERT INTO plugin_files (id, plugin_id, file_url, type, version) VALUES (:id, :plugin_id, :file_url, :type, :version)';

        if (! $this->pdo->inTransaction()) {
            $ourTransaction = true;
            $this->pdo->beginTransaction();
        }

        try {
            foreach ($versions as $version => $url) {
                $this->pdo->perform($sql, [
                    'id'        => Uuid::uuid7()->toString(),
                    'plugin_id' => $pluginId->toString(),
                    'file_url'  => $url,
                    'type'      => $cdn,
                    'version'   => $version,
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
     * @return array|string[]
     */
    public function checkPluginInDatabase(string $slug): array
    {
        if (isset($this->existing[$slug])) {
            return $this->existing[$slug];
        }

        return [];
    }

    /**
     * @param  array<string, string|array<string, string>>  $fileContents
     * @return array|string[]
     */
    public function updatePluginFromWP(array $fileContents, string $pulledAt): array
    {
        if (isset($fileContents['error']) && $fileContents['error'] === 'closed') {
            return $this->updateClosedPlugin($fileContents, $pulledAt);
        } else {
            return $this->updateOpenPlugin($fileContents, $pulledAt);
        }
    }

    /**
     * @param  array<string, string|array<string, string>>  $fileContents
     * @return array|string[]
     */
    private function updateClosedPlugin(array $fileContents, string $pulledAt): array
    {
        if (! empty($fileContents['closed_date'])) {
            $closedDate = date('c', strtotime($fileContents['closed_date']));
        } else {
            $closedDate = date('c');
        }
        try {
            $this->pdo->beginTransaction();

            $sql = 'UPDATE plugins SET status = :status, pulled_at = :pulled_at, updated = :updated WHERE slug = :slug';
            $this->pdo->perform(
                $sql,
                [
                    'status'    => 'closed',
                    'pulled_at' => $pulledAt,
                    'slug'      => $fileContents['slug'],
                    'updated'   => $closedDate,
                ]
            );
            $this->pdo->commit();
            return ['error' => ''];
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  array<string, string|array<string, string>>  $fileContents
     * @return array|string[]
     */
    private function updateOpenPlugin(array $fileContents, string $pulledAt): array
    {
        $this->pdo->beginTransaction();

        try {
            $name           = substr($fileContents['name'], 0, 255);
            $slug           = $fileContents['slug'];
            $currentVersion = $fileContents['version'];
            $versions       = $fileContents['versions'];
            $updatedAt      = date('c', strtotime($fileContents['last_updated']));

            $sql = 'SELECT id FROM plugins WHERE slug = :slug';
            $id  = Uuid::fromString($this->pdo->fetchValue($sql, ['slug' => $slug]));

            $sql = 'UPDATE plugins SET name = :name, current_version = :current_version, status = :status, updated = :updated, pulled_at = :pulled_at WHERE slug = :slug';
            $this->pdo->perform($sql, [
                'name'            => $name,
                'slug'            => $slug,
                'current_version' => $currentVersion,
                'status'          => 'open',
                'updated'         => $updatedAt,
                'pulled_at'       => $pulledAt,
            ]);

            if (! isset($fileContents['versions']) || empty($fileContents['versions'])) {
                $versions = [$fileContents['version'] => $fileContents['download_link']];
            } else {
                $versions = $fileContents['versions'];
            }

            $newVersions = $this->getNewlyDiscoveredVersionsList($id->toString(), $versions);

            $versionResult = $this->writeVersionsForPlugin($id, $newVersions, 'wp_cdn');

            if (! empty($versionResult['error'])) {
                throw new Exception('Unable to write versions for plugin ' . $slug);
            }

            $this->pdo->commit();
            return ['error' => ''];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * @param  string[]  $versions
     * @return string[]
     */
    private function getNewlyDiscoveredVersionsList(string $id, array $versions): array
    {
        $existingVersions = 'SELECT version FROM plugin_files WHERE plugin_id = :id';
        $existingVersions = $this->pdo->fetchAll($existingVersions, ['id' => $id]);

        $newVersions = [];
        foreach ($versions as $version => $url) {
            if (! in_array($version, $existingVersions)) {
                $newVersions[$version] = $url;
            }
        }

        return $newVersions;
    }

    /**
     * @return array<string, string[]>
     */
    private function loadExistingPlugins(): array
    {
        $sql    = 'SELECT slug, status, pulled_at FROM plugins';
        $result = [];
        foreach ($this->pdo->fetchAll($sql) as $row) {
            $result[$row['slug']] = ['status' => $row['status'], 'pulled_at' => $row['pulled_at']];
        }

        return $result;
    }
}
