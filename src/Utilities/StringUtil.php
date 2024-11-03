<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

abstract class StringUtil
{
    /** @return string[] */
    public static function explodeAndTrim(string $string, string $separator = ','): array
    {
        $parts = explode($separator, $string);
        array_walk($parts, fn(&$str) => $str = trim($str));
        return array_values(array_filter($parts, fn($str) => $str !== ''));
    }
}
