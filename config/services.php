<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use AspirePress\AspireSync\Factories\AwsS3V3AdapterFactory;
use AspirePress\AspireSync\Factories\ExtendedPdoFactory;
use AspirePress\AspireSync\Factories\GuzzleClientFactory;
use Aura\Sql\ExtendedPdoInterface;
use GuzzleHttp\Client as GuzzleClient;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\expr;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $env = fn(string $name, ?string $default = null) => ($_ENV[$name] ?? null) ?: $default;

    $parameters = $containerConfigurator->parameters();
    $parameters->set('fstype', $env('DOWNLOADS_FILESYSTEM', 'local'));
    $parameters->set('db_file', $env('DB_FILE', realpath(__DIR__ . '/../data/aspiresync.sqlite')));
    $parameters->set('db_init_file', $env('DB_INIT_FILE', realpath(__DIR__ . '/../config/schema.sql')));
    $parameters->set('download_dir', $env('DOWNLOAD_DIR', dirname(__DIR__) . '/data/download'));
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
        // ->bind('string $adminEmail', 'manager@example.com')
        // ->bind(LoggerInterface::class . ' $requestLogger', service('monolog.logger.request'))

    $services->load('AspirePress\\AspireSync\\', '../src/');



    $services->set(AwsS3V3Adapter::class)->factory(service(AwsS3V3AdapterFactory::class));
    $services->set(ExtendedPdoInterface::class)->factory(service(ExtendedPdoFactory::class));
    $services->set(GuzzleClient::class)->factory(service(GuzzleClientFactory::class));

    $services->set(LocalFilesystemAdapter::class)->args([param('download_dir')]);

    $services->alias('fs.adapter.s3', AwsS3V3Adapter::class);
    $services->alias('fs.adapter.local', LocalFilesystemAdapter::class);

    $services->set(Filesystem::class)->args([expr("service('fs.adapter.' ~ parameter('fstype'))")]);

    // The wiring for this class is bonkers, so it's been banished to the attic for now
    // $services->set(UtilUploadCommand::class)->factory(service(UtilUploadCommandFactory::class));
};
