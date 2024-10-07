<?php

declare(strict_types=1);

namespace AssetGrabber\Tests\Helpers;

use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

abstract class FunctionalTestHelper
{
    private static ServiceManager $container;
    public static function setContainer(ServiceManager $container): void
    {
        self::$container = $container;
    }

    public static function getService(string $name): mixed
    {
        return self::$container->get($name);
    }

    public static function getContainer(): ServiceManager
    {
        return self::$container;
    }

    public static function getDatabaseConnection(): ExtendedPdoInterface
    {
        return self::$container->get(ExtendedPdoInterface::class);
    }

    public static function resetDatabase(): void
    {
        $pdo = self::getDatabaseConnection();
        $pdo->perform('TRUNCATE plugins CASCADE');
        $pdo->perform('TRUNCATE themes CASCADE');
        $pdo->perform('TRUNCATE revisions CASCADE');
        $pdo->perform('TRUNCATE stats CASCADE');
        $pdo->perform('TRUNCATE not_found_items CASCADE');
    }
}
