<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Survos\ArkBundle\Contract\ArkableInterface;
use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
#[AutoconfigureTag('doctrine.event_listener')]
final class ArkDoctrineListener
{
    public function __construct(
        private readonly NoidMinterService $minter,
        private readonly bool $autoMint = true,
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ArkableInterface) {
            return;
        }

        if ($entity->getArk() !== null) {
            return;
        }

        if (!$this->autoMint) {
            return;
        }

        $noid = $this->minter->mint();
        $ark = $this->minter->buildFullArk($noid);
        $entity->setArk($ark);

        $target = $entity->getArkTarget();
        if ($target !== '') {
            $this->minter->bind($noid, $target);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof ArkableInterface) {
            return;
        }

        $ark = $entity->getArk();
        if ($ark === null) {
            return;
        }

        if (!$args->hasChangedField('ark')) {
            return;
        }

        $changeSet = $args->getObjectManager()->getUnitOfWork()->getEntityChangeSet($entity);
        if (!isset($changeSet['ark'])) {
            return;
        }

        $oldTarget = $changeSet['ark'][0] ?? null;
        $newTarget = $entity->getArkTarget();

        if ($oldTarget !== $newTarget && $newTarget !== '') {
            $noid = $this->minter->extractNoid($ark);
            if ($noid !== null) {
                $this->minter->rebind($noid, $newTarget);
            }
        }
    }
}
