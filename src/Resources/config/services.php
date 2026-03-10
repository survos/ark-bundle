<?php

declare(strict_types=1);

use Museado\ArkBundle\Command\BindCommand;
use Museado\ArkBundle\Command\BulkMintCommand;
use Museado\ArkBundle\Command\MintCommand;
use Museado\ArkBundle\Command\ReindexCommand;
use Museado\ArkBundle\Command\ReportCommand;
use Museado\ArkBundle\Command\ResolveCommand;
use Museado\ArkBundle\Command\ValidateCommand;
use Museado\ArkBundle\Controller\ArkRedirectController;
use Museado\ArkBundle\Doctrine\ArkDoctrineListener;
use Museado\ArkBundle\Service\ArkRegistry;
use Museado\ArkBundle\Service\NoidMinterService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('museado_ark.minter', NoidMinterService::class)
            ->args([
                '%museado_ark.naan%',
                '%museado_ark.shoulder%',
                '%museado_ark.template%',
                '%museado_ark.db_type%',
                '%museado_ark.db_path%',
            ])
            ->tag('kernel.reset', ['method' => 'close'])

        ->set('museado_ark.registry', ArkRegistry::class)
            ->args([
                service('museado_ark.minter'),
                '%museado_ark.resolver_base_url%',
                service('doctrine')->ignoreOnInvalid(),
            ])

        ->set('museado_ark.doctrine_listener', ArkDoctrineListener::class)
            ->args([
                service('museado_ark.minter'),
                '%museado_ark.auto_mint%',
            ])
            ->tag('doctrine.event_listener', ['event' => 'prePersist'])
            ->tag('doctrine.event_listener', ['event' => 'preUpdate'])

        ->set('museado_ark.controller.redirect', ArkRedirectController::class)
            ->args([
                service('museado_ark.registry'),
                '%museado_ark.naan%',
                '%museado_ark.n2t_resolve%',
            ])

        ->set(MintCommand::class)
            ->args([service('museado_ark.minter')])
            ->tag('console.command')

        ->set(BindCommand::class)
            ->args([service('museado_ark.minter')])
            ->tag('console.command')

        ->set(ResolveCommand::class)
            ->args([service('museado_ark.minter')])
            ->tag('console.command')

        ->set(ValidateCommand::class)
            ->args([service('museado_ark.minter')])
            ->tag('console.command')

        ->set(BulkMintCommand::class)
            ->args([
                service('museado_ark.minter'),
                service('doctrine')->ignoreOnInvalid(),
            ])
            ->tag('console.command')

        ->set(ReportCommand::class)
            ->args([
                service('doctrine')->ignoreOnInvalid(),
            ])
            ->tag('console.command')

        ->set(ReindexCommand::class)
            ->args([
                service('museado_ark.minter'),
                service('doctrine')->ignoreOnInvalid(),
            ])
            ->tag('console.command')
    ;
};
