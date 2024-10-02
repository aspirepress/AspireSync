<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use Aura\Sql\ExtendedPdoInterface;
use PDOException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsImportMetaCommand extends Command
{
    /** @var string[] */
    private array $existing;

    public function __construct(private ExtendedPdoInterface $pdo)
    {
        parent::__construct();
        $this->existing = $this->loadExistingPlugins();
    }

    public function configure(): void
    {
        $this->setName('plugins:import-meta')
            ->setDescription('Import metadata from JSON files into Postgres');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = scandir('/opt/assetgrabber/data/plugin-raw-data');
        $files = count($files);
        if ($files > 2) {
            $count = $files - 2;
        } else {
            $count = 0;
        }
        $output->writeln('Importing ' . $count . ' files...');

        foreach ($files as $file) {
            if (strpos($file, '.json') !== false) {
                $this->pdo->beginTransaction();

                $fileContents = file_get_contents('/opt/assetgrabber/data/plugin-raw-data/' . $file);
                $fileContents = json_decode($fileContents, true);

                $pulledAt = date('c', filemtime('/opt/assetgrabber/data/plugin-raw-data/' . $file));

                // Check for existing
                $exists = $this->checkPluginInDatabase($fileContents['slug'] ?? '');
                if ($exists) {
                    $output->writeln('NOTICE: Skipping plugin ' . $fileContents['slug'] . ' as it exists in DB already...');
                    $this->pdo->rollBack();
                    continue;
                }

                if (isset($fileContents['error'])) {
                    if ($fileContents['error'] !== 'closed') {
                        $output->writeln('ERROR: Skipping; unable to write file ' . $file);
                        $this->pdo->rollBack();
                        continue;
                    }

                    $sql = "INSERT INTO plugins (id, name, slug, status, updated, pulled_at) VALUES (:id, :name, :slug, :status, :closed_date, :pulled_at)";

                    if (isset($fileContents['closed_date']) && ! empty($fileContents['closed_date'])) {
                        $closedDate = date('c', strtotime($fileContents['closed_date']));
                    } else {
                        $closedDate = date('c');
                    }

                    $output->writeln('Writing CLOSED plugin ' . $fileContents['slug']);
                    try {
                        $this->pdo->perform($sql, [
                            'id'          => Uuid::uuid7()->toString(),
                            'name'        => substr($fileContents['name'], 0, 255),
                            'slug'        => $fileContents['slug'],
                            'closed_date' => $closedDate,
                            'pulled_at'   => $pulledAt,
                            'status'      => $fileContents['error'],
                        ]);
                    } catch (PDOException $e) {
                        $this->pdo->rollBack();
                        $output->writeln('ERROR: ' . $e->getMessage());
                        $output->writeln('ERROR: Unable to write CLOSED plugin ' . $fileContents['slug']);
                        continue;
                    }
                } else {
                    try {
                        $name           = substr($fileContents['name'], 0, 255);
                        $slug           = $fileContents['slug'];
                        $currentVersion = $fileContents['version'];
                        $versions       = $fileContents['versions'];
                        $updatedAt      = date('c', strtotime($fileContents['last_updated']));
                        $id             = Uuid::uuid7();

                        $output->writeln('Writing OPEN plugin ' . $slug);
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

                        $sql = 'INSERT INTO plugin_files (id, plugin_id, file_url, type, version) VALUES (:id, :plugin_id, :file_url, :type, :version)';
                        if (! empty($versions)) {
                            $output->writeln('Writing ' . count($versions) . ' versions of ' . $slug);
                            foreach ($versions as $version => $url) {
                                $this->pdo->perform($sql, [
                                    'id'        => Uuid::uuid7()->toString(),
                                    'plugin_id' => $id->toString(),
                                    'file_url'  => $url,
                                    'type'      => 'wp_cdn',
                                    'version'   => $version,
                                ]);
                            }
                        } else {
                            $output->writeln('Writing  SINGLE VERSION of ' . $slug);
                            $url     = $fileContents['download_link'];
                            $version = $fileContents['version'];
                            $this->pdo->perform($sql, [
                                'id'        => Uuid::uuid7()->toString(),
                                'plugin_id' => $id->toString(),
                                'file_url'  => $url,
                                'type'      => 'wp_cdn',
                                'version'   => $version,
                            ]);
                        }
                    } catch (PDOException $e) {
                        $this->pdo->rollBack();
                        $output->writeln('ERROR: ' . $e->getMessage());
                        $output->writeln('ERROR: Unable to write OPEN plugin ' . $slug);
                        continue;
                    }
                }

                $this->pdo->commit();
            }
        }

        $output->writeln('Done!');
        return self::SUCCESS;
    }

    private function checkPluginInDatabase(string $slug): bool
    {
        return isset($this->existing[$slug]);
    }

    /**
     * @return string[]
     */
    private function loadExistingPlugins(): array
    {
        $sql    = 'SELECT slug, status FROM plugins';
        $result = [];
        foreach ($this->pdo->fetchAll($sql) as $row) {
            $result[$row['slug']] = $row['status'];
        }

        return $result;
    }
}
