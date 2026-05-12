<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ark:validate', 'Validate an ARK name segment.')]
final class ValidateCommand
{
    public function __construct(private readonly NoidMinterService $minter)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('ARK name segment')] string $name,
    ): int {
        $valid = $this->minter->validate($name);

        $io->writeln($valid ? 'valid' : 'invalid');

        return $valid ? Command::SUCCESS : Command::FAILURE;
    }
}
