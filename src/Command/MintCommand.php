<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ark:mint', 'Mint one or more ARK identifiers.')]
final class MintCommand
{
    public function __construct(private readonly NoidMinterService $minter)
    {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Number of ARKs to mint')] int $count = 1,
        #[Option('Output format (text|json)')] string $output = 'text',
    ): int {
        $count = max(1, $count);
        $arks = [];
        for ($i = 0; $i < $count; ++$i) {
            $name = $this->minter->mint();
            $arks[] = $this->minter->buildFullArk($name);
        }

        if (strtolower($output) === 'json') {
            $io->writeln((string) json_encode($arks, JSON_PRETTY_PRINT));
            return Command::SUCCESS;
        }

        foreach ($arks as $ark) {
            $io->writeln($ark);
        }

        return Command::SUCCESS;
    }
}
