<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ark:bind', 'Bind an ARK name to a URL.')]
final class BindCommand
{
    public function __construct(private readonly NoidMinterService $minter)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('ARK name segment')] string $name,
        #[Argument('Target URL')] string $url,
    ): int {
        $this->minter->bind($name, $url);
        $io->writeln(sprintf('Bound %s', $name));

        return Command::SUCCESS;
    }
}
