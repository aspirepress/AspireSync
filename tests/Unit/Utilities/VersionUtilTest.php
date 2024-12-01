<?php

declare(strict_types=1);

namespace App\Tests\Unit\Utilities;

use App\Utilities\VersionUtil;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VersionUtilTest extends TestCase
{
    /**
     * @param array<int, string> $versions
     * @param array<int, string> $correctOrder
     */
    #[DataProvider('versionSortData')]
    public function testVersionSort(array $versions, array $correctOrder): void
    {
        $result = VersionUtil::sortVersions($versions);
        $this->assertEquals($correctOrder, $result);
    }

    /**
     * @param array<int, string> $versions
     * @param array<int, string> $correctOrder
     */
    #[DataProvider('versionSortAscDataProvider')]
    public function testVersionSortAsc(array $versions, array $correctOrder): void
    {
        $result = VersionUtil::sortVersionsAsc($versions);
        $this->assertEquals($correctOrder, $result);
    }

    public function testLimitVersions(): void
    {
        $versions = ['1.2.3', '1.2.1', '1.1.1', '1.1.0.1', '1.1', '1.0.1'];
        $result   = VersionUtil::limitVersions($versions, 2);
        $this->assertEquals(['1.2.3', '1.2.1'], $result);

        $result = VersionUtil::limitVersions($versions, 4);
        $this->assertEquals(['1.2.3', '1.2.1', '1.1.1', '1.1.0.1'], $result);

        $result = VersionUtil::limitVersions($versions, 1000);
        $this->assertEquals($versions, $result);
    }

    public function testGetLatestVersion(): void
    {
        $versions = ['1.0.1', '1.2.1', '1.1', '1.2.3', '1.1.1', '1.1.0.1'];
        $result   = VersionUtil::getLatestVersion($versions);
        $this->assertEquals('1.2.3', $result);
    }

    /**
     * @return array<int, array<int, array<int, string>>>
     */
    public static function versionSortData(): array
    {
        return [
            [
                ['1.2.3', '1.0.1', '1.2.1', '1.1', '1.1.1', '1.12.1', 'trunk', '1.1.0.1'],
                ['1.12.1', '1.2.3', '1.2.1', '1.1.1', '1.1.0.1', '1.1', '1.0.1', 'trunk'],
            ],
        ];
    }

    /**
     * @return array<int, array<int, array<int, string>>>
     */
    public static function versionSortAscDataProvider(): array
    {
        return [
            [
                ['1.2.3', '1.0.1', '1.2.1', '1.1', '1.1.1', '1.1.0.1'],
                ['1.0.1', '1.1', '1.1.0.1', '1.1.1', '1.2.1', '1.2.3'],
            ],
        ];
    }
}
