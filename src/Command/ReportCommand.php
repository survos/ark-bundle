<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Survos\ArkBundle\Contract\ArkableInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ark:report', 'Show ARK status report.')]
final class ReportCommand
{
    public function __construct(private readonly ?ManagerRegistry $doctrine = null)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        if ($this->doctrine === null) {
            $io->error('Doctrine is not available.');
            return Command::FAILURE;
        }

        $rows = [];
        foreach ($this->doctrine->getManagers() as $manager) {
            $meta = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($meta as $classMetadata) {
                $className = $classMetadata->getName();
                if (!is_a($className, ArkableInterface::class, true)) {
                    continue;
                }

                $repo = $manager->getRepository($className);
                $entities = $repo->findAll();
                $total = count($entities);
                $minted = 0;
                foreach ($entities as $entity) {
                    if ($entity->getArk() !== null) {
                        ++$minted;
                    }
                }
                $rows[] = [$className, (string) $total, (string) $minted, (string) ($total - $minted)];
            }
        }

        $io->table(['Entity', 'Total', 'Minted', 'Unminted'], $rows);

        return Command::SUCCESS;
    }
}
