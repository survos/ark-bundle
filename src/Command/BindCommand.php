<?php

declare(strict_types=1);

namespace Survos\ArkBundle\Command;

use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ark:bind', description: 'Bind an ARK name to a URL.')]
final class BindCommand extends Command
{
    public function __construct(private readonly NoidMinterService $minter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'ARK name segment')
            ->addArgument('url', InputArgument::REQUIRED, 'Target URL');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = (string) $input->getArgument('name');
        $url = (string) $input->getArgument('url');

        $this->minter->bind($name, $url);
        $output->writeln(sprintf('Bound %s', $name));

        return Command::SUCCESS;
    }
}
