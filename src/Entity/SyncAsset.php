<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SyncAssetRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SyncAssetRepository::class)]
#[ORM\Table(name: 'sync_assets')]
#[ORM\UniqueConstraint(name: 'uniq_syncid_version', columns: ['sync_id', 'version'])]
#[ORM\Index(name: 'idx_created', columns: ['created'])]
#[ORM\Index(name: 'idx_processed', columns: ['processed'])]
class SyncAsset
{
    #[ORM\Column(nullable: true)]
    public ?DateTimeImmutable $processed = null;  // mutable!

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
        #[ORM\GeneratedValue(strategy: 'CUSTOM')]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        #[ORM\Id]
        public readonly Uuid $id,

        #[ORM\JoinColumn(name: 'sync_id', nullable: false)]
        #[ORM\ManyToOne(inversedBy: 'assets')]
        public readonly SyncResource $resource,

        #[ORM\Column(length: 32)]
        public readonly string $version,

        #[ORM\Column(type: Types::TEXT, nullable: true)]
        public readonly string $url,

        #[ORM\Column]
        public readonly DateTimeImmutable $created,

        #[ORM\Column(nullable: true)]
        public readonly ?array $metadata = null,
    ) {}
}
