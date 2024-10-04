<?php

declare(strict_types=1);

namespace AssetGrabber\Utilities;

abstract class ListManagementUtil
{
    /**
     * @return array<int, string>
     */
    public static function explodeCommaSeparatedList(?string $list): array
    {
        if (empty($list)) {
            return [];
        }

        $list = explode(',', $list);
        array_walk($list, function (&$value) { $value = trim($value, ','); });
        return $list;
    }
}