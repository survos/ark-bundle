<?php

declare(strict_types=1);

namespace Museado\ArkBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Museado\ArkBundle\Contract\ArkableInterface;
use Museado\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ark:reindex', description: 'Reindex ARK bindings from entities.')]
final class ReindexCommand extends Command
{
    public function __construct(
        private readonly NoidMinterService $minter,
        private readonly ?ManagerRegistry $doctrine = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Preview changes only');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->doctrine === null) {
            $output->writeln('Doctrine is not available.');

            return Command::FAILURE;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $count = 0;

        foreach ($this->doctrine->getManagers() as $manager) {
            $meta = $manager->getMetadataFactory()->getAllMetadata();
            foreach ($meta as $classMetadata) {
                $className = $classMetadata->getName();
                if (!is_a($className, ArkableInterface::class, true)) {
                    continue;
                }

                $entities = $manager->getRepository($className)->findAll();
                foreach ($entities as $entity) {
                    $ark = $entity->getArk();
                    if ($ark === null) {
                        continue;
                    }

                    $name = $this->minter->extractNoid($ark);
                    if ($name === null) {
                        continue;
                    }

                    if (!$dryRun) {
                        $this->minter->rebind($name, $entity->getArkTarget());
                    }

                    ++$count;
                }
            }
        }

        $output->writeln(sprintf('Reindexed %d ARKs.', $count));

        return Command::SUCCESS;
    }
}
