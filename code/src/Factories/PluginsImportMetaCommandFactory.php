<?php

declare(strict_types=1);

namespace AssetGrabber\Factories;

use AssetGrabber\Commands\PluginsImportMetaCommand;
use Aura\Sql\ExtendedPdoInterface;
use Laminas\ServiceManager\ServiceManager;

class PluginsImportMetaCommandFactory
{
    public function __invoke(ServiceManager $serviceManager): PluginsImportMetaCommand
    {
        $pdo = $serviceManager->get(ExtendedPdoInterface::class);
        return new PluginsImportMetaCommand($pdo);
    }
}
