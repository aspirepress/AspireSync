<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use AspirePress\AspireSync\Factories\AwsS3V3AdapterFactory;
use AspirePress\AspireSync\Factories\ConnectionFactory;
use AspirePress\AspireSync\Factories\ExtendedPdoFactory;
use AspirePress\AspireSync\Factories\GuzzleClientFactory;
use Aura\Sql\ExtendedPdoInterface;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client as GuzzleClient;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    global $APP_DIR;
    $env = fn(string $name, mixed $default = null) => ($_ENV[$name] ?? null) ?: $default;

    $downloads_dir = $env('DOWNLOADS_DIR', "$APP_DIR/data/download");
    if (! str_starts_with($downloads_dir, '/')) {
        $downloads_dir = "$APP_DIR/$downloads_dir";
    }

    $db_file = $env('DB_FILE', "$APP_DIR/data/aspiresync.sqlite");
    $db_url  = $env('DB_URL', "sqlite3:///$db_file");

    $parameters = $containerConfigurator->parameters();
    $parameters->set('db_file', $db_file);
    $parameters->set('db_url', $db_url);
    $parameters->set('db_init_file', $env('DB_INIT_FILE', realpath(__DIR__ . '/../config/schema.sql')));
    $parameters->set('downloads_dir', $downloads_dir);
    $parameters->set('fstype', $env('DOWNLOADS_FILESYSTEM', 'local'));
    $parameters->set('s3_bucket', $env('S3_BUCKET', null));
    $parameters->set('s3_key', $env('S3_KEY', null));
    $parameters->set('s3_secret', $env('S3_SECRET', null));
    $parameters->set('s3_region', $env('S3_REGION', null));
    $parameters->set('s3_endpoint', $env('S3_ENDPOINT', null));

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('AspirePress\\AspireSync\\', '../src/');

    $services->set(AwsS3V3Adapter::class)->factory(service(AwsS3V3AdapterFactory::class));

    $services->set(Connection::class)->factory(service(ConnectionFactory::class));
    $services->set(ExtendedPdoInterface::class)->factory(service(ExtendedPdoFactory::class));

    $services->set(GuzzleClient::class)->factory(service(GuzzleClientFactory::class));

    $services->set(LocalFilesystemAdapter::class)->args([param('downloads_dir')]);

    $services->alias('fs.adapter.s3', AwsS3V3Adapter::class);
    $services->alias('fs.adapter.local', LocalFilesystemAdapter::class);

    $services->set(Filesystem::class)->args([expr("service('fs.adapter.' ~ parameter('fstype'))")]);
};
