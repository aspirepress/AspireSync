<?php

namespace AspirePress\AspireSync\Services\Interfaces;

use Closure;

interface CacheServiceInterface {
    public function remember(string $key, int $ttl, Closure $callback): mixed;

    public function forget(string $key): void;

    public function clear(): void;
}