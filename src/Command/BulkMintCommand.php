<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Survos\ArkBundle\Contract\ArkableInterface;
use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ark:bulk-mint', 'Mint ARKs for all unminted arkable entities.')]
final class BulkMintCommand
{
    public function __construct(
        private readonly NoidMinterService $minter,
        private readonly ?ManagerRegistry $doctrine = null,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Preview changes only')] bool $dryRun = false,
    ): int {
        if ($this->doctrine === null) {
            $io->writeln('Doctrine is not available.');
            return Command::FAILURE;
        }

        $minted = 0;

        foreach ($this->doctrine->getManagers() as $manager) {
            $meta = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($meta as $classMetadata) {
                $className = $classMetadata->getName();
                if (!is_a($className, ArkableInterface::class, true)) {
                    continue;
                }

                $entities = $manager->getRepository($className)->findBy(['ark' => null]);
                foreach ($entities as $entity) {
                    $name = $this->minter->mint();
                    $entity->setArk($this->minter->buildFullArk($name));
                    $this->minter->bind($name, $entity->getArkTarget());
                    ++$minted;

                    if (!$dryRun) {
                        $manager->persist($entity);
                    }
                }
            }

            if (!$dryRun) {
                $manager->flush();
            }
        }

        $io->writeln(sprintf('Minted %d ARKs.', $minted));

        return Command::SUCCESS;
    }
}
