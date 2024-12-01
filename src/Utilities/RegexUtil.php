<?php

declare(strict_types=1);

namespace App\Utilities;

// This class is normally just named 'Regex' elsewhere, but it gets the "Util" suffix in AS for consistency.

use Safe\Exceptions\PcreException;

use function Safe\preg_grep;
use function Safe\preg_match;
use function Safe\preg_match_all;
use function Safe\preg_replace;
use function Safe\preg_split;

class RegexUtil
{
    /** @return string[] */
    public static function match(string $pattern, string $subject, int $flags = 0, int $offset = 0): array
    {
        $matches = [];
        preg_match($pattern, $subject, $matches, $flags, $offset);
        return $matches;
    }

    /** @return string[][] */
    public static function matchAll(string $pattern, string $subject, int $flags = 0, int $offset = 0): array
    {
        $matches = [];
        preg_match_all($pattern, $subject, $matches, $flags, $offset);
        return $matches;
    }

    /**
     * Here for consistency: no difference from \Safe\preg_split
     *
     * @return string[]
     */
    public static function split(string $pattern, string $subject, ?int $limit = -1, int $flags = 0): array
    {
        return preg_split($pattern, $subject, $limit, $flags);
    }

    /**
     * Here for consistency: no difference from \Safe\preg_grep
     *
     * @param string[] $array
     * @return string[]
     */
    public static function grep(string $pattern, array $array, int $flags = 0): array
    {
        return preg_grep($pattern, $array, $flags);
    }

    /**
     * Fits the most common use case of preg_replace, namely single strings.  Does not support the by-ref $count arg.
     */
    public static function replace(string $pattern, string $replacement, string $subject, int $limit = -1): string
    {
        return static::_replace($pattern, $replacement, $subject, $limit);
    }

    /**
     * Equivalent to \Safe\preg_replace, inherits the insane signature of the builtin preg_replace
     *
     * @param string|string[] $pattern
     * @param string|string[] $replacement
     * @param string|string[] $subject
     * @return string|string[]
     * @throws PcreException
     */
    public static function _replace(
        string|array $pattern,
        string|array $replacement,
        string|array $subject,
        int $limit = -1,
        ?int &$count = null,
    ): string|array {
        return preg_replace($pattern, $replacement, $subject, $limit, $count);
    }

    private function __construct()
    {
        // not instantiable
    }
}
