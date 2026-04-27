<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Entity;

use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\ArkBundle\Repository\ArkBindingRepository;
use Survos\FieldBundle\Attribute\EntityMeta;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;

/**
 * Pivot table linking every minted ARK to the entity it names.
 * Resolution goes here first — one SQL query instead of scanning every
 * ArkableInterface class. Entities that use ArkableTrait still carry the
 * ark string directly; this table is the index on top of that storage.
 */
#[EntityMeta(icon: 'mdi:identifier', group: 'ARK')]
#[ORM\Entity(repositoryClass: ArkBindingRepository::class)]
#[ORM\Table(name: 'ark_binding')]
#[ORM\Index(fields: ['entityClass', 'entityId'], name: 'idx_ark_binding_entity')]
#[ApiResource(
    operations: [
        new Get(uriTemplate: '/ark-bindings/{id}'),
        new GetCollection(uriTemplate: '/ark-bindings'),
    ],
    normalizationContext: ['groups' => ['ark_binding:read']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'entityClass' => 'exact',
    'entityId'    => 'exact',
    'ark'         => 'exact',
])]
#[ApiFilter(OrderFilter::class, properties: ['createdAt', 'ark', 'entityClass'])]
class ArkBinding
{
    #[ORM\Id]
    #[ORM\Column(length: 26)]
    #[Groups(['ark_binding:read'])]
    #[ApiProperty(identifier: true, description: 'ULID row identifier.')]
    public private(set) string $id;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['ark_binding:read'])]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(
        /** Full ARK string, e.g. ark:/12345/ab12345 */
        #[ORM\Column(length: 255, unique: true)]
        #[Groups(['ark_binding:read'])]
        #[ApiProperty(description: 'Full ARK string, e.g. ark:/12345/ab12345.', example: 'ark:/12345/ab12345')]
        public private(set) string $ark,

        /** Short entity class name, e.g. "Scan", "Item". App-defined; no FK enforced. */
        #[ORM\Column(length: 64)]
        #[Groups(['ark_binding:read'])]
        #[ApiProperty(description: 'Short entity class name. App-defined; no FK enforced.', example: 'Scan')]
        public private(set) string $entityClass,

        /** Entity identifier as a string (ULID, UUID, or auto-increment cast to string). */
        #[ORM\Column(length: 64)]
        #[Groups(['ark_binding:read'])]
        #[ApiProperty(description: 'Entity identifier as string.', example: '01J3KXYZ1234567890ABCDEFGH')]
        public private(set) string $entityId,

        /** Cached redirect target; kept in sync by ArkDoctrineListener on entity update. */
        #[ORM\Column(length: 2048, nullable: true)]
        #[Groups(['ark_binding:read'])]
        #[ApiProperty(description: 'Cached redirect URL for this ARK.')]
        public private(set) ?string $targetUrl = null,

        /** Optional human-readable label used in admin browse. */
        #[ORM\Column(length: 255, nullable: true)]
        #[Groups(['ark_binding:read'])]
        #[ApiProperty(description: 'Optional human-readable label for browse views.')]
        public private(set) ?string $label = null,
    ) {
        $this->id        = (string) new Ulid();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function updateTarget(string $targetUrl): static
    {
        $this->targetUrl = $targetUrl;

        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'createdAt'   => $this->createdAt->format(\DateTimeInterface::ATOM),
            'ark'         => $this->ark,
            'entityClass' => $this->entityClass,
            'entityId'    => $this->entityId,
            'targetUrl'   => $this->targetUrl,
            'label'       => $this->label,
        ];
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        foreach (['ark', 'entityClass', 'entityId'] as $field) {
            if (!isset($row[$field]) || !\is_string($row[$field]) || $row[$field] === '') {
                throw new \InvalidArgumentException(sprintf('ArkBinding row is missing required field "%s".', $field));
            }
        }

        $binding = new self(
            ark: $row['ark'],
            entityClass: $row['entityClass'],
            entityId: $row['entityId'],
            targetUrl: isset($row['targetUrl']) && \is_string($row['targetUrl']) ? $row['targetUrl'] : null,
            label: isset($row['label']) && \is_string($row['label']) ? $row['label'] : null,
        );

        if (isset($row['id']) && \is_string($row['id']) && $row['id'] !== '') {
            $binding->id = $row['id'];
        }

        if (isset($row['createdAt']) && \is_string($row['createdAt']) && $row['createdAt'] !== '') {
            $binding->createdAt = new \DateTimeImmutable($row['createdAt']);
        }

        return $binding;
    }
}
