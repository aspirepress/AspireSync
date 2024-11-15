<?php
declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

// This class is normally just named 'Regex' elsewhere, but it gets the "Util" suffix in AS for consistency.

class RegexUtil {
    /** @return string[] */
    public static function match(string $pattern, string $subject, int $flags = 0, int $offset = 0): array
    {
        $matches = [];
        \Safe\preg_match($pattern, $subject, $matches, $flags, $offset);
        return $matches;
    }

    /** @return string[][] */
    public static function matchAll(string $pattern, string $subject, int $flags = 0, int $offset = 0): array
    {
        $matches = [];
        \Safe\preg_match_all($pattern, $subject, $matches, $flags, $offset);
        return $matches;
    }

    /**
     * Here for consistency: no difference from \Safe\preg_split
     *
     * @return string[]
     */
    public static function split(string $pattern, string $subject, ?int $limit = -1, int $flags = 0): array
    {
        return \Safe\preg_split($pattern, $subject, $limit, $flags);
    }

    /**
     * Here for consistency: no difference from \Safe\preg_grep
     *
     * @return string[]
     */
    public static function grep(string $pattern, array $array, int $flags = 0): array
    {
        return \Safe\preg_grep($pattern, $array, $flags);
    }

    private function __construct()
    {
        // not instantiable
    }
}
