<?php

declare(strict_types=1);

namespace Museado\ArkBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Museado\ArkBundle\Contract\ArkableInterface;

final class ArkRegistry
{
    public function __construct(
        private readonly NoidMinterService $minter,
        private readonly string $resolverBaseUrl,
        private readonly ?ManagerRegistry $doctrine = null,
    ) {}

    public function resolve(string $name): ?string
    {
        $resolved = $this->minter->resolve($name);
        if ($resolved !== null) {
            return $this->normalizeUrl($resolved);
        }

        if ($this->doctrine === null) {
            return null;
        }

        foreach ($this->doctrine->getManagers() as $manager) {
            $meta = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($meta as $classMetadata) {
                $className = $classMetadata->getName();
                if (!is_a($className, ArkableInterface::class, true)) {
                    continue;
                }

                $ark = sprintf('ark:/%s/%s', $this->minter->getNaan(), $name);
                $entity = $manager->getRepository($className)->findOneBy(['ark' => $ark]);
                if (!$entity instanceof ArkableInterface) {
                    continue;
                }

                $target = $this->normalizeUrl($entity->getArkTarget());
                $this->minter->rebind($name, $target);

                return $target;
            }
        }

        return null;
    }

    private function normalizeUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return rtrim($this->resolverBaseUrl, '/') . '/' . ltrim($url, '/');
    }
}
