<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Utilities;

abstract class VersionUtil
{
    /**
     * @param array<int, string> $versions
     */
    public static function getLatestVersion(array $versions): string
    {
        $versionList = self::sortVersions($versions);
        return array_shift($versionList);
    }

    /**
     * @param string[] $versions
     * @return array<int, string>
     */
    public static function limitVersions(array $versions, int $limit): array
    {
        return array_slice($versions, 0, $limit);
    }

    /**
     * @param array<int, string> $versions
     * @return array<int, string>
     */
    public static function sortVersions(array $versions): array
    {
        return array_reverse(self::getParsedVersions($versions));
    }

    /**
     * @param string[] $versions
     * @return array<int, string>
     */
    public static function sortVersionsAsc(array $versions): array
    {
        return self::getParsedVersions($versions);
    }

    /**
     * @param array<int, string> $versions
     * @return array<int, string>
     * Sorts versions in order from earliest to latest (e.g. 1.0, 1.1., 2.0)
     */
    private static function getParsedVersions(array $versions): array
    {
        usort($versions, function ($a, $b) {
            // Split the versions into parts and convert to integers
            $aParts = array_map('intval', explode('.', $a));
            $bParts = array_map('intval', explode('.', $b));

            // Compare each part of the version
            for ($i = 0; $i < max(count($aParts), count($bParts)); $i++) {
                $aPart = $aParts[$i] ?? 0; // Default to 0 if part doesn't exist
                $bPart = $bParts[$i] ?? 0; // Default to 0 if part doesn't exist

                if ($aPart < $bPart) {
                    return -1; // $a is less than $b
                }
                if ($aPart > $bPart) {
                    return 1; // $a is greater than $b
                }
            }

            return 0; // They are equal
        });

        return $versions;
    }
}
