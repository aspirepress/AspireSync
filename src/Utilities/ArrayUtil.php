<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

class ArrayUtil
{
    public static function entries(array $arr): array
    {
        return array_map(fn($key) => [$key, $arr[$key]], array_keys($arr));
    }

    public static function fromEntries(iterable $entries): array
    {
        $assoc = [];
        foreach ($entries as [$key, $value]) {
            $assoc[$key] = $value;
        }
        return $assoc;
    }

    private function __construct()
    {
        // not instantiable
    }
}
