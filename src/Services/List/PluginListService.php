<?php

declare(strict_types=1);

namespace App\Services\List;

use App\ResourceType;
use App\Services\Interfaces\SubversionServiceInterface;
use App\Services\Metadata\PluginMetadataService;
use Doctrine\ORM\EntityManagerInterface;

class PluginListService extends AbstractListService
{
    public function __construct(SubversionServiceInterface $svn, PluginMetadataService $meta, EntityManagerInterface $em)
    {
        parent::__construct($svn, $meta, $em, ResourceType::Plugin);
    }
}
