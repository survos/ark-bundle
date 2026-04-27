<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Survos\ArkBundle\Entity\ArkBinding;

/**
 * @extends ServiceEntityRepository<ArkBinding>
 */
class ArkBindingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArkBinding::class);
    }

    public function findByArk(string $ark): ?ArkBinding
    {
        return $this->findOneBy(['ark' => $ark]);
    }

    /** @return iterable<ArkBinding> */
    public function iterateForExport(?string $entityClass = null): iterable
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'ASC')
            ->addOrderBy('b.id', 'ASC');

        if ($entityClass !== null) {
            $qb->andWhere('b.entityClass = :entityClass')
               ->setParameter('entityClass', $entityClass);
        }

        return $qb->getQuery()->toIterable();
    }
}
