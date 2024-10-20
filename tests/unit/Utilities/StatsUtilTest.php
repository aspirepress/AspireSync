<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Unit\Utilities;

use AspirePress\AspireSync\Utilities\StatsUtil;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StatsUtilTest extends TestCase
{
    public function testStatsDefinedProperly(): void
    {
        StatsUtil::defineStats(['foo', 'bar', 'baz']);
        $stats = StatsUtil::getStats(StatsUtil::RAW);
        $this->assertEquals(
            [
                'foo' => 0,
                'bar' => 0,
                'baz' => 0,
            ],
            $stats
        );
    }

    public function testStatsDefinedOneByOneWorks(): void
    {
        StatsUtil::defineStat('foo');
        StatsUtil::defineStat('bar');
        StatsUtil::defineStat('baz');
        $stats = StatsUtil::getStats(StatsUtil::RAW);
        $this->assertEquals(
            [
                'foo' => 0,
                'bar' => 0,
                'baz' => 0,
            ],
            $stats
        );
    }

    public function testCanNotSetStatPreviouslyDefined(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StatsUtil::defineStat('foo');
        StatsUtil::defineStat('foo');
    }

    public function testStatsAddsCorrectly(): void
    {
        StatsUtil::defineStats(['foo', 'bar', 'baz']);
        StatsUtil::add('foo');
        StatsUtil::add('bar', 1);
        StatsUtil::add('baz', 2);
        $stats = StatsUtil::getStats(StatsUtil::RAW);
        $this->assertEquals(
            [
                'foo' => 1,
                'bar' => 1,
                'baz' => 2,
            ],
            $stats
        );
    }

    public function testStatsCanNotAddToNonDefinedStat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        StatsUtil::defineStats(['foo', 'bar', 'baz']);
        StatsUtil::add('bin');
    }

    public function testStatFormattingFormatsCorrectly(): void
    {
        StatsUtil::defineStats(['fooshort', 'fooveryverylong', 'baz']);
        StatsUtil::add('fooshort');
        StatsUtil::add('fooveryverylong', 2);
        StatsUtil::add('baz', 3);
        $stats = StatsUtil::getStats();
        $this->assertEquals(
            [
                'Fooshort:        1',
                'Fooveryverylong: 2',
                'Baz:             3',
            ],
            $stats
        );
    }

    public function testResetStatValuesWorks(): void
    {
        StatsUtil::defineStats(['foo', 'bar', 'baz']);
        StatsUtil::add('foo');
        StatsUtil::increment('bar');
        StatsUtil::increment('bar');
        StatsUtil::add('baz', 4);
        $stats = StatsUtil::getStats(StatsUtil::RAW);
        $this->assertEquals(
            [
                'foo' => 1,
                'bar' => 2,
                'baz' => 4,
            ],
            $stats
        );

        StatsUtil::reset(StatsUtil::RESET_VALUES);
        $stats = StatsUtil::getStats(StatsUtil::RAW);
        $this->assertEquals(
            [
                'foo' => 0,
                'bar' => 0,
                'baz' => 0,
            ],
            $stats
        );
    }

    protected function tearDown(): void
    {
        StatsUtil::reset();
    }
}
