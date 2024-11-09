<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

use InvalidArgumentException;

abstract class StatsUtil
{
    /** @var array<string, int> */
    private static array $stats = [];

    public const true RAW        = true;
    public const false FORMATTED = false;

    public const int RESET_ALL = 1;

    public const int RESET_VALUES = 2;

    /**
     * @param array<int, string> $stats
     */
    public static function defineStats(array $stats = []): void
    {
        foreach ($stats as $stat) {
            self::defineStat($stat);
        }
    }

    public static function defineStat(string $stat): void
    {
        if (! isset(self::$stats[$stat])) {
            self::$stats[$stat] = 0;
            return;
        }

        throw new InvalidArgumentException('Unable to add previously defined stat!');
    }

    public static function add(string $stat, int $amount = 1): void
    {
        if (! isset(self::$stats[$stat])) {
            throw new InvalidArgumentException('Unable to add undefined stat!');
        }

        self::$stats[$stat] += $amount;
    }

    public static function increment(string $stat): void
    {
        self::add($stat, 1);
    }

    /**
     * @return array<string, int>|array<int, string>
     */
    public static function getStats(bool $style = self::FORMATTED): array
    {
        if ($style === self::RAW) {
            return self::$stats;
        }

        $stats   = self::$stats;
        $longest = 0;
        $result  = [];
        foreach ($stats as $stat => $amount) {
            if (strlen($stat) > $longest) {
                $longest = strlen($stat) + 1;
            }

            $result[ucfirst($stat)] = $amount;
        }

        $formatted = [];
        foreach ($result as $stat => $amount) {
            $stat       .= ":";
            $stat        = str_pad($stat, $longest, ' ', STR_PAD_RIGHT);
            $formatted[] = $stat . " " . $amount;
        }

        return $formatted;
    }

    public static function reset(int $reset = self::RESET_ALL): void
    {
        if ($reset === self::RESET_ALL) {
            self::$stats = [];
        } else {
            foreach (self::$stats as $stat => $amount) {
                self::$stats[$stat] = 0;
            }
        }
    }
}
