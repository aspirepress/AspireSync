<?php

declare(strict_types=1);

namespace App\Services\List;

use App\ResourceType;
use App\Services\Interfaces\SubversionServiceInterface;
use App\Services\Metadata\ThemeMetadataService;
use Doctrine\ORM\EntityManagerInterface;

class ThemeListService extends AbstractListService
{
    public function __construct(SubversionServiceInterface $svn, ThemeMetadataService $meta, EntityManagerInterface $em)
    {
        parent::__construct($svn, $meta, $em, ResourceType::Theme);
    }
}
