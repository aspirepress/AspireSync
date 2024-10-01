<?php

declare(strict_types=1);

namespace AssetGrabber\Commands;

use Aura\Sql\ExtendedPdoInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsImportMetaCommand extends Command
{
    public function __construct(private ExtendedPdoInterface $pdo)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName('plugins:import-meta')
            ->setDescription('Import metadata from JSON files into Postgres');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $files = scandir('/opt/assetgrabber/data/plugin-raw-data');

        foreach ($files as $file) {
            if (strpos($file, '.json') !== false) {
                $this->pdo->beginTransaction();

                $fileContents = file_get_contents('/opt/assetgrabber/data/plugin-raw-data/' . $file);
                $fileContents = json_decode($fileContents, true);

                $pulledAt = date('c', filemtime('/opt/assetgrabber/data/plugin-raw-data/' . $file));
                if (isset($fileContents['error'])) {
                    if ($fileContents['error'] !== 'closed') {
                        $this->pdo->rollBack();
                        continue;
                    }
                    $sql = "INSERT INTO plugins (id, name, slug, status, updated, pulled_at) VALUES (:id, :name, :slug, :status, :closed_date, :pulled_at)";

                    if (isset($fileContents['closed_date']) && !empty($fileContents['closed_date'])) {
                        $closedDate = date('c', strtotime($fileContents['closed_date']));
                    } else {
                        $closedDate = date('c');
                    }

                    $this->pdo->perform($sql, [
                        'id' => Uuid::uuid7()->toString(),
                        'name' => $fileContents['name'],
                        'slug' => $fileContents['slug'],
                        'closed_date' => $closedDate,
                        'pulled_at' => $pulledAt,
                        'status' => $fileContents['error'] === 'closed' ? 'closed' : $fileContents['error'],
                    ]);
                } else {

                    $name = $fileContents['name'];
                    $slug = $fileContents['slug'];
                    $currentVersion = $fileContents['version'];
                    $versions = $fileContents['versions'];
                    $updatedAt = date('c', strtotime($fileContents['last_updated']));
                    $id = Uuid::uuid7();

                    $sql = 'INSERT INTO plugins (id, name, slug, current_version, status, updated, pulled_at) VALUES (:id, :name, :slug, :current_version, :status, :updated_at, :pulled_at)';
                    $this->pdo->perform($sql, [
                        'id' => $id->toString(),
                        'name' => $name,
                        'slug' => $slug,
                        'current_version' => $currentVersion,
                        'status' => 'open',
                        'updated_at' => $updatedAt,
                        'pulled_at' => $pulledAt,
                    ]);

                    $sql = 'INSERT INTO plugin_files (id, plugin_id, file_url, type, version) VALUES (:id, :plugin_id, :file_url, :type, :version)';
                    if (!empty($versions)) {
                        foreach ($versions as $version => $url) {
                            $this->pdo->perform($sql, [
                                'id' => Uuid::uuid7()->toString(),
                                'plugin_id' => $id->toString(),
                                'file_url' => $url,
                                'type' => 'wp_cdn',
                                'version' => $version,
                            ]);
                        }
                    } else {
                        $url = $fileContents['download_link'];
                        $version = $fileContents['version'];
                        $this->pdo->perform($sql, [
                            'id' => Uuid::uuid7()->toString(),
                            'plugin_id' => $id->toString(),
                            'file_url' => $url,
                            'type' => 'wp_cdn',
                            'version' => $version,
                        ]);
                    }
                }
                $this->pdo->commit();
            }
        }

        return self::SUCCESS;
    }
}