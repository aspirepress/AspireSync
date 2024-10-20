<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Functional;

use AspirePress\AspireSync\Tests\Helpers\FunctionalTestHelper;
use PHPUnit\Framework\TestCase;

abstract class AbstractFunctionalTestBase extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        FunctionalTestHelper::resetDatabase();
    }
}
