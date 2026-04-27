<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Entity\ArkBinding;
use Survos\ArkBundle\Repository\ArkBindingRepository;
use Survos\JsonlBundle\IO\JsonlWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ark:export',
    description: 'Export ARK bindings as JSONL to stdout or a file.',
)]
final class ArkExportCommand
{
    public function __construct(
        private readonly ArkBindingRepository $bindings,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Restrict export to one entity class (short name, e.g. "Scan").')]
        ?string $entityClass = null,
        #[Option('Write to a .jsonl or .jsonl.gz file instead of stdout.')]
        ?string $output = null,
    ): int {
        if ($output !== null && !class_exists(JsonlWriter::class)) {
            $io->error("jsonl-bundle is not installed.\n\ncomposer req survos/jsonl-bundle");

            return Command::FAILURE;
        }

        $count     = 0;
        $writer    = $output !== null ? JsonlWriter::open($output) : null;
        $completed = false;

        try {
            foreach ($this->bindings->iterateForExport($entityClass) as $binding) {
                \assert($binding instanceof ArkBinding);

                $row = $binding->toArray();

                if ($writer !== null) {
                    $writer->write($row);
                } else {
                    $json = \json_encode($row, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
                    if ($json === false) {
                        throw new \RuntimeException(sprintf('Failed to encode binding %s.', $row['id'] ?? '[unknown]'));
                    }
                    fwrite(\STDOUT, $json . "\n");
                }

                ++$count;
            }

            $completed = true;
        } finally {
            if ($writer !== null) {
                $completed ? $writer->finish() : $writer->close();
            }
        }

        if ($output !== null) {
            $io->success(sprintf('Exported %d binding(s) to %s.', $count, $output));
        } else {
            $io->writeln(sprintf('Exported %d binding(s).', $count), OutputInterface::VERBOSITY_VERBOSE);
        }

        return Command::SUCCESS;
    }
}
