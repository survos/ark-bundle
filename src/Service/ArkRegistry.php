<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Survos\ArkBundle\Contract\ArkableInterface;
use Survos\ArkBundle\Contract\ArkQualifiableInterface;

final class ArkRegistry
{
    public function __construct(
        private readonly NoidMinterService $minter,
        private readonly string $resolverBaseUrl,
        private readonly ?ManagerRegistry $doctrine = null,
    ) {}

    public function resolve(string $name): ?string
    {
        [$baseName, $qualifier] = $this->splitName($name);

        $baseTarget = $this->resolveBaseName($baseName);
        if ($baseTarget === null) {
            return null;
        }

        if ($qualifier === null || $qualifier === '') {
            return $baseTarget;
        }

        $parent = $this->findArkableByName($baseName);
        if ($parent === null) {
            return $baseTarget;
        }

        $qualifiedTarget = $this->findQualifiedTarget($parent, $qualifier);

        return $qualifiedTarget ?? $baseTarget;
    }

    private function resolveBaseName(string $name): ?string
    {
        $resolved = $this->minter->resolve($name);
        if ($resolved !== null) {
            return $this->normalizeUrl($resolved);
        }

        $entity = $this->findArkableByName($name);
        if (!$entity instanceof ArkableInterface) {
            return null;
        }

        $target = $this->normalizeUrl($entity->getArkTarget());
        $this->minter->rebind($name, $target);

        return $target;
    }

    private function findArkableByName(string $name): ?ArkableInterface
    {
        if ($this->doctrine === null) {
            return null;
        }

        $ark = sprintf('ark:/%s/%s', $this->minter->getNaan(), $name);

        foreach ($this->doctrine->getManagers() as $manager) {
            $meta = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($meta as $classMetadata) {
                $className = $classMetadata->getName();
                if (!is_a($className, ArkableInterface::class, true)) {
                    continue;
                }

                $entity = $manager->getRepository($className)->findOneBy(['ark' => $ark]);
                if ($entity instanceof ArkableInterface) {
                    return $entity;
                }
            }
        }

        return null;
    }

    private function findQualifiedTarget(ArkableInterface $parent, string $qualifier): ?string
    {
        if ($this->doctrine === null) {
            return null;
        }

        foreach ($this->doctrine->getManagers() as $manager) {
            $meta = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($meta as $classMetadata) {
                $className = $classMetadata->getName();
                if (!is_a($className, ArkQualifiableInterface::class, true)) {
                    continue;
                }

                $candidates = $manager->getRepository($className)->findAll();
                foreach ($candidates as $candidate) {
                    if ($candidate->getArkQualifier() !== $qualifier) {
                        continue;
                    }

                    if (!$this->isSameEntity($manager, $candidate->getArkParent(), $parent)) {
                        continue;
                    }

                    return $this->normalizeUrl($candidate->getArkTarget());
                }
            }
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private function splitName(string $name): array
    {
        if (!str_contains($name, '/')) {
            return [$name, null];
        }

        [$base, $qualifier] = explode('/', $name, 2);

        return [$base, $qualifier !== '' ? $qualifier : null];
    }

    private function isSameEntity(ObjectManager $manager, object $a, object $b): bool
    {
        if ($a === $b) {
            return true;
        }

        if ($a::class !== $b::class) {
            return false;
        }

        $meta = $manager->getClassMetadata($a::class);
        $aId = $meta->getIdentifierValues($a);
        $bId = $meta->getIdentifierValues($b);

        return $aId !== [] && $aId === $bId;
    }

    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($this->resolverBaseUrl, '/') . '/' . ltrim($url, '/');
    }
}
