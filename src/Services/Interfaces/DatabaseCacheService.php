<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Services\Interfaces;

use Closure;
use Doctrine\DBAL\Connection;
use function Safe\json_decode;
use function Safe\json_encode;

class DatabaseCacheService implements CacheServiceInterface
{

    private string $table = 'cache';

    public function __construct(private Connection $connection)
    {
    }

    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        $conn = $this->connection;
        $sql = "select value from {$this->table} where key = :key and expires > current_timestamp";

        $row = $conn->fetchAssociative($sql, ['key' => $key]);
        if ($row) {
            return json_decode($row['value'], true);
        }

        $value = $callback();
        $expires = date('c', strtotime("+$ttl seconds"));

        $conn->beginTransaction();
        $conn->delete($this->table, ['key' => $key]);
        $conn->insert($this->table, ['key' => $key, 'value' => json_encode($value), 'expires' => $expires]);
        $conn->commit();

        return $value;
    }

    public function forget(string $key): void
    {
        $this->connection->executeStatement("delete from {$this->table} where key = ?", [$key]);
    }

    public function clear(): void
    {
        $this->connection->executeStatement("delete from {$this->table} where true");
    }
}
