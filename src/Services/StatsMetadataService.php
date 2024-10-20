<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services;

use Aura\Sql\ExtendedPdoInterface;
use Ramsey\Uuid\Uuid;

class StatsMetadataService
{
    public function __construct(private ExtendedPdoInterface $pdo)
    {
    }

    /**
     * @param array<string, int> $stats
     */
    public function logStats(string $command, array $stats = []): void
    {
        $id    = Uuid::uuid7();
        $stats = json_encode($stats);
        $sql   = 'INSERT INTO stats(id, stats, command, created_at) VALUES (:id, :stats, :command, NOW())';
        $this->pdo->perform($sql, ['id' => $id->toString(), 'stats' => $stats, 'command' => $command]);
    }
}
