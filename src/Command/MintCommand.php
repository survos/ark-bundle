<?php

declare(strict_types=1);

namespace Museado\ArkBundle\Command;

use Museado\ArkBundle\Service\NoidMinterService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'ark:mint', description: 'Mint one or more ARK identifiers.')]
final class MintCommand extends Command
{
    public function __construct(private readonly NoidMinterService $minter)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('count', InputArgument::OPTIONAL, 'Number of ARKs to mint', 1)
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output format (text|json)', 'text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $count = max(1, (int) $input->getArgument('count'));
        $arks = [];
        for ($i = 0; $i < $count; ++$i) {
            $name = $this->minter->mint();
            $arks[] = $this->minter->buildFullArk($name);
        }

        $format = strtolower((string) $input->getOption('output'));
        if ($format === 'json') {
            $output->writeln((string) json_encode($arks, JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        }

        foreach ($arks as $ark) {
            $output->writeln($ark);
        }

        return Command::SUCCESS;
    }
}
