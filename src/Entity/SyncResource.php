<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SyncResourceRepository;
use App\ResourceType;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SyncResourceRepository::class)]
#[ORM\Table(name: 'sync')]
#[ORM\UniqueConstraint(name: 'uniq_type_slug_origin', fields: ['type', 'slug', 'origin'])]
#[ORM\Index(name: 'idx_slug', fields: ['slug'])]
#[ORM\Index(name: 'idx_type', fields: ['type'])]
#[ORM\Index(name: 'idx_origin', fields: ['origin'])]
#[ORM\Index(name: 'idx_updated', fields: ['updated'])]
#[ORM\Index(name: 'idx_pulled', fields: ['pulled'])]
class SyncResource
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
        #[ORM\GeneratedValue(strategy: 'CUSTOM')]
        #[ORM\Column(type: UuidType::NAME, unique: true)]
        #[ORM\Id]
        public readonly Uuid $id,

        #[ORM\Column(length: 32)]
        public readonly ResourceType $type,

        #[ORM\Column(length: 255)]
        public readonly string $slug,

        #[ORM\Column(type: Types::TEXT)]
        public readonly string $name,

        #[ORM\Column(length: 32)]
        public readonly string $status,

        #[ORM\Column(length: 32, nullable: true)]
        public readonly ?string $version,

        #[ORM\Column(length: 32)]
        public readonly string $origin,

        // when this record was synced
        #[ORM\Column]
        public readonly int $pulled,

        // last updated date in metadata
        #[ORM\Column]
        public readonly ?int $updated,

        #[ORM\Column(nullable: true)]
        public readonly ?array $metadata = null,
    ) {
        $this->assets = new ArrayCollection();
    }

    /** @var Collection<int, SyncAsset> */
    #[ORM\OneToMany(targetEntity: SyncAsset::class, mappedBy: 'resource')]
    public readonly Collection $assets;
}
