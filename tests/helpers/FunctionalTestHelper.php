<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Tests\Helpers;

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

    public static function getDb(): ExtendedPdoInterface
    {
        return self::$container->get(ExtendedPdoInterface::class);
    }

    public static function resetDatabase(): void
    {
        $pdo = self::getDb();
        $pdo->perform('TRUNCATE sync_plugins CASCADE');
        $pdo->perform('TRUNCATE sync_themes CASCADE');
        $pdo->perform('TRUNCATE sync_revisions CASCADE');
        $pdo->perform('TRUNCATE sync_stats CASCADE');
        $pdo->perform('TRUNCATE sync_not_found_items CASCADE');
    }
}
