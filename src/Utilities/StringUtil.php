<?php

declare(strict_types=1);

namespace App\Utilities;

abstract class StringUtil
{
    /** @return string[] */
    public static function explodeAndTrim(string $string, string $pattern = '/[\s,;]+/'): array
    {
        $parts = \Safe\preg_split($pattern, $string);
        array_walk($parts, fn(&$str) => $str = trim($str));
        return array_values(array_filter($parts, fn($str) => $str !== ''));
    }
}
