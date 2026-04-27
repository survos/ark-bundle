<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Entity\ArkBinding;
use Survos\ArkBundle\Repository\ArkBindingRepository;
use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ark:reindex', description: 'Rebuild the NOID database from the ark_binding table.')]
final class ReindexCommand
{
    public function __construct(
        private readonly NoidMinterService $minter,
        private readonly ?ArkBindingRepository $bindings = null,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Preview changes without writing to the NOID database.')]
        bool $dryRun = false,
    ): int {
        if ($this->bindings === null) {
            $io->error('ArkBindingRepository is not available — is Doctrine configured?');

            return Command::FAILURE;
        }

        $count   = 0;
        $skipped = 0;

        foreach ($this->bindings->iterateForExport() as $binding) {
            \assert($binding instanceof ArkBinding);

            if ($binding->targetUrl === null) {
                ++$skipped;
                continue;
            }

            $name = $this->minter->extractNoid($binding->ark);
            if ($name === null) {
                ++$skipped;
                continue;
            }

            if (!$dryRun) {
                $this->minter->rebind($name, $binding->targetUrl);
            }

            ++$count;
        }

        $io->success(sprintf(
            '%s %d binding(s)%s.',
            $dryRun ? 'Would reindex' : 'Reindexed',
            $count,
            $skipped > 0 ? sprintf(' (%d skipped — no target URL)', $skipped) : '',
        ));

        return Command::SUCCESS;
    }
}
