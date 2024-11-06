<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use AspirePress\AspireSync\Factories\ExtendedPdoFactory;
use AspirePress\AspireSync\Factories\GuzzleClientFactory;
use Aura\Sql\ExtendedPdoInterface;
use GuzzleHttp\Client as GuzzleClient;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set('db_file', $_ENV['DB_FILE'] ?? realpath(__DIR__ . '/../data/aspiresync.sqlite'));
    $parameters->set('db_init_file', $_ENV['DB_INIT_FILE'] ?? realpath(__DIR__ . '/../config/schema.sql'));

    $services = $containerConfigurator->services();

    $services->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();
        // ->bind('string $adminEmail', 'manager@example.com')
        // ->bind(LoggerInterface::class . ' $requestLogger', service('monolog.logger.request'))

    $services->load('AspirePress\\AspireSync\\', '../src/');

    $services->set(ExtendedPdoInterface::class)->factory(service(ExtendedPdoFactory::class));
    $services->set(GuzzleClient::class)->factory(service(GuzzleClientFactory::class));

    // The wiring for this class is bonkers, so it's been banished to the attic for now
    // $services->set(UtilUploadCommand::class)->factory(service(UtilUploadCommandFactory::class));
};
