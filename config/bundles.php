<?php

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;

return [
    FrameworkBundle::class => ['all' => true],
    FlysystemBundle::class => ['all' => true],
    DoctrineBundle::class => ['all' => true],
    MonologBundle::class => ['all' => true],
    MakerBundle::class => ['dev' => true],
    DoctrineMigrationsBundle::class => ['all' => true],
];
