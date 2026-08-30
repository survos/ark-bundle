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
    // Nullable because SurvosArkBundle registers this argument with
    // ignoreOnInvalid(), so that the bundle stays installable in an app that
    // does not map its entities. ignoreOnInvalid() injects NULL, so a
    // non-nullable type here is an invalid service definition by construction --
    // `lint:container` rejects it, and it would TypeError at runtime. The guard
    // in __invoke() turns that into a usable message instead.
    public function __construct(
        private readonly ?ArkBindingRepository $bindings = null,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Restrict export to one entity class (short name, e.g. "Scan").')]
        ?string $entityClass = null,
        #[Option('Write to a .jsonl or .jsonl.gz file instead of stdout.')]
        ?string $output = null,
    ): int {
        if (null === $this->bindings) {
            $io->error(
                "ArkBundle entities are not mapped in this application, so export is unavailable.\n\n"
                . "Add the bundle's entities to config/packages/doctrine.yaml:\n\n"
                . "    doctrine:\n"
                . "        orm:\n"
                . "            mappings:\n"
                . "                SurvosArkBundle:\n"
                . "                    type: attribute\n"
                . "                    dir: '%kernel.project_dir%/vendor/survos/ark-bundle/src/Entity'\n"
                . "                    prefix: 'Survos\\ArkBundle\\Entity'\n"
                . "                    is_bundle: false"
            );

            return Command::FAILURE;
        }

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
