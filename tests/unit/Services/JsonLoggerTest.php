<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Unit\Services;

use AspirePress\AspireSync\Services\JsonLogger;
use PHPUnit\Framework\TestCase;

class JsonLoggerTest extends TestCase
{
    private JsonLogger $sut;

    protected function setUp(): void
    {
        $this->sut = new JsonLogger();
    }

    public function test_normalize_level(): void
    {
        $this->assertEquals('debug', JsonLogger::normalizeLevel('debug'));
        $this->assertEquals('info', JsonLogger::normalizeLevel('INFO'));
        $this->assertEquals('emergency', JsonLogger::normalizeLevel('eMeRgEnCy'));
        $this->assertEquals('critical', JsonLogger::normalizeLevel(2));
    }

    public function test_integer_levels_out_of_bounds_are_clamped(): void
    {
        $this->assertEquals('debug', JsonLogger::normalizeLevel(999));
        $this->assertEquals('emergency', JsonLogger::normalizeLevel(-1));
    }

    public function test_all_int_strings_are_debug(): void
    {
        // TODO: check whether this violates PSR-3.  Meantime, just don't do this!
        $this->assertEquals('debug', JsonLogger::normalizeLevel('5'));
    }
}
