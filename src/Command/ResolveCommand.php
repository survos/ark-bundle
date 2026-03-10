<?php

declare(strict_types=1);

namespace Museado\ArkBundle\Command;

use Museado\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ark:resolve', description: 'Resolve an ARK name to URL.')]
final class ResolveCommand extends Command
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
        $resolved = $this->minter->resolve($name);

        if ($resolved === null) {
            $output->writeln('Not found.');

            return Command::FAILURE;
        }

        $output->writeln($resolved);

        return Command::SUCCESS;
    }
}
