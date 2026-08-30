<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Survos\ArkBundle\Entity\ArkBinding;
use Survos\JsonlBundle\IO\JsonlReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ark:import',
    description: 'Import ARK bindings from JSONL on stdin or from a file.',
)]
final class ArkImportCommand
{
    // Nullable for the same reason as ArkExportCommand: the bundle registers
    // this with ignoreOnInvalid() so it stays installable without Doctrine.
    // Latent rather than currently failing -- an app with Doctrine but no Ark
    // mapping still resolves the entity manager -- but the definition is wrong
    // in exactly the same way for an app with no Doctrine at all.
    public function __construct(
        private readonly ?EntityManagerInterface $em = null,
    ) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Option('Read from a .jsonl or .jsonl.gz file instead of stdin.')]
        ?string $input = null,
        #[Option('Flush every N imported rows.')]
        int $batchSize = 250,
        #[Option('Skip rows whose id already exists in the database.')]
        bool $skipExisting = true,
    ): int {
        if (null === $this->em) {
            $io->error(
                "ArkBundle entities are not mapped in this application, so import is unavailable.\n\n"
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

        if ($input !== null && !class_exists(JsonlReader::class)) {
            $io->error("jsonl-bundle is not installed.\n\ncomposer req survos/jsonl-bundle");

            return Command::FAILURE;
        }

        $batchSize = max(1, $batchSize);
        $imported  = 0;
        $skipped   = 0;
        $line      = 0;

        foreach ($this->rows($input) as $row) {
            ++$line;

            try {
                $binding = ArkBinding::fromArray($row);
            } catch (\Throwable $e) {
                $io->error(sprintf('Invalid JSONL row at line %d: %s', $line, $e->getMessage()));

                return Command::FAILURE;
            }

            if ($skipExisting && $this->em->find(ArkBinding::class, $binding->id)) {
                ++$skipped;
                continue;
            }

            $this->em->persist($binding);
            ++$imported;

            if (($imported % $batchSize) === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        $this->em->flush();
        $this->em->clear();

        $io->success(sprintf(
            'Imported %d binding(s)%s.',
            $imported,
            $skipped > 0 ? sprintf(', skipped %d existing', $skipped) : '',
        ));

        return Command::SUCCESS;
    }

    /** @return iterable<array<string, mixed>> */
    private function rows(?string $input): iterable
    {
        if ($input !== null) {
            foreach (JsonlReader::open($input) as $row) {
                yield $row;
            }

            return;
        }

        while (($line = fgets(\STDIN)) !== false) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);
            if (!\is_array($decoded)) {
                throw new \RuntimeException('Encountered a non-object JSONL row on stdin.');
            }

            yield $decoded;
        }
    }
}
