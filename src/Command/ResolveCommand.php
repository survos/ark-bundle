<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ark:resolve', 'Resolve an ARK name to URL.')]
final class ResolveCommand
{
    public function __construct(private readonly NoidMinterService $minter)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('ARK name segment')] string $name,
    ): int {
        $resolved = $this->minter->resolve($name);

        if ($resolved === null) {
            $io->writeln('Not found.');
            return Command::FAILURE;
        }

        $io->writeln($resolved);

        return Command::SUCCESS;
    }
}
