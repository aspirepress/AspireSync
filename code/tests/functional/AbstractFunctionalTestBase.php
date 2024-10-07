<?php

declare(strict_types=1);

namespace AssetGrabber\Tests\Functional;

use AssetGrabber\Tests\Helpers\FunctionalTestHelper;

abstract class AbstractFunctionalTestBase extends \PHPUnit\Framework\TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        FunctionalTestHelper::resetDatabase();
    }
}