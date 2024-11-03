<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

abstract class ListManagementUtil
{
    /** @return string[] */
    public static function explodeCommaSeparatedList(?string $str): array
    {
        if (! $str) {
            return [];
        }
        $list = explode(',', $str);
        array_walk($list, function (&$value) {
            $value = trim($value, ',');
        });
        return $list;
    }
}
