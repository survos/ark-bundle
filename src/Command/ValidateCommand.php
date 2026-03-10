<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ark:validate', description: 'Validate an ARK name segment.')]
final class ValidateCommand extends Command
{
    public function __construct(private readonly NoidMinterService $minter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'ARK name segment');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $valid = $this->minter->validate($name);

        $output->writeln($valid ? 'valid' : 'invalid');

        return $valid ? Command::SUCCESS : Command::FAILURE;
    }
}
