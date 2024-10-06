<?php

declare(strict_types=1);

namespace AssetGrabber\Services;

use Aura\Sql\ExtendedPdoInterface;

class StatsMetadataService
{
    public function __construct(private ExtendedPdoInterface $pdo)
    {
    }

    public function logStats(string $command, array $stats = []): void
    {
        $id = Uuid::uuid7();
        $stats = json_encode($stats);
        $sql = 'INSERT INTO stats(id, stats, command, created_at) VALUES (:id, :stats, :command, NOW())';
        $this->pdo->perform($sql, ['id' => $id->toString(), 'stats' => $stats, 'command' => $command]);
    }
}