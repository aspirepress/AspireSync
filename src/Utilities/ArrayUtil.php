<?php

declare(strict_types=1);

namespace App\Utilities;

class ArrayUtil
{
    /**
     * @param array<string|int, mixed> $arr
     * @return array{string|int, mixed}[]
     */
    public static function entries(array $arr): array
    {
        return array_map(fn($key) => [$key, $arr[$key]], array_keys($arr));
    }

    /**
     * @param iterable<array{string|int, mixed}> $entries
     * @return array<string|int, mixed>
     */
    public static function fromEntries(iterable $entries): array
    {
        $assoc = [];
        foreach ($entries as [$key, $value]) {
            $assoc[$key] = $value;
        }
        return $assoc;
    }

    /**
     * @param array<string|int,mixed> $arr
     * @param string[] $keys
     * @return array<string|int,mixed>
     */
    public static function onlyKeys(array $arr, array $keys): array
    {
        return array_intersect_key($arr, array_flip($keys));
    }

    /**
     * @param array<string|int,mixed> $arr
     * @param string[] $keys
     * @return array<string|int,mixed>
     */
    public static function withoutKeys(array $arr, array $keys): array
    {
        return array_diff_key($arr, array_flip($keys));
    }

    private function __construct()
    {
        // not instantiable
    }
}
