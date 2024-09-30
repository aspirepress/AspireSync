<?php

declare(strict_types=1);

namespace AssertGrabber\Tests\Unit\Utilities;

use AssetGrabber\Utilities\VersionUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionUtilTest extends TestCase
{
    #[DataProvider('versionSortData')]
    public function testVersionSort(array $versions, array $correctOrder): void
    {
        $result = VersionUtil::sortVersions($versions);
        $this->assertEquals($correctOrder, $result);
    }

    #[DataProvider('versionSortAscDataProvider')]
    public function testVersionSortAsc(array $versions, array $correctOrder): void
    {
        $result = VersionUtil::sortVersionsAsc($versions);
        $this->assertEquals($correctOrder, $result);
    }

    public function testLimitVersions(): void
    {
        $versions = ['1.2.3', '1.2.1', '1.1.1', '1.1.0.1', '1.1', '1.0.1'];
        $result = VersionUtil::limitVersions($versions, 2);
        $this->assertEquals(['1.2.3', '1.2.1'], $result);

        $result = VersionUtil::limitVersions($versions, 4);
        $this->assertEquals(['1.2.3', '1.2.1', '1.1.1', '1.1.0.1'], $result);

        $result = VersionUtil::limitVersions($versions, 1000);
        $this->assertEquals($versions, $result);
    }

    public function testGetLatestVersion(): void
    {
        $versions = ['1.0.1', '1.2.1', '1.1', '1.2.3', '1.1.1', '1.1.0.1'];
        $result = VersionUtil::getLatestVersion($versions);
        $this->assertEquals('1.2.3', $result);
    }

    public static function versionSortData(): array
    {
        return [
            [
                ['1.2.3', '1.0.1', '1.2.1', '1.1', '1.1.1', '1.1.0.1'],
                ['1.2.3', '1.2.1', '1.1.1', '1.1.0.1', '1.1', '1.0.1']
            ],
        ];
    }

    public static function versionSortAscDataProvider(): array
    {
        return [
            [
                ['1.2.3', '1.0.1', '1.2.1', '1.1', '1.1.1', '1.1.0.1'],
                ['1.0.1', '1.1', '1.1.0.1', '1.1.1', '1.2.1', '1.2.3']
            ],
        ];
    }
}
