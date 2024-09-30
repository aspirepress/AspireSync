<?php

namespace AssetGrabber\Utilities;

abstract class VersionUtil
{
    public static function getLatestVersion(array $versions): string
    {
        $versionList = self::sortVersions($versions);
        return array_shift($versionList);
    }

    public static function limitVersions(array $versions, $limit): array
    {
        return array_slice($versions, 0, $limit);
    }

    public static function sortVersions(array $versions): array
    {
        $parsedVersions = self::getParsedVersions($versions);

        arsort($parsedVersions);

        return array_keys($parsedVersions);
    }

    public static function sortVersionsAsc(array $versions): array
    {
        $parsedVersions = self::getParsedVersions($versions);

        asort($parsedVersions);

        return array_keys($parsedVersions);
    }

    private static function getParsedVersions(array $versions): array
    {
        $parsedVersions = [];
        foreach ($versions as $version) {
            $parts = explode('.', $version);
            if (!isset($parts[1])) {
                $parts[1] = 0;
            }

            if (!isset($parts[2])) {
                $parts[2] = 0;
            }
            if (!isset($parts[3])) {
                $parts[3] = 0;
            }

            $parsedVersions[$version] = implode('.', $parts);
        }

        return $parsedVersions;
    }
}