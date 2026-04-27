<?php

declare(strict_types=1);

use Survos\ArkBundle\Command\ArkExportCommand;
use Survos\ArkBundle\Command\ArkImportCommand;
use Survos\ArkBundle\Command\BindCommand;
use Survos\ArkBundle\Command\BulkMintCommand;
use Survos\ArkBundle\Command\MintCommand;
use Survos\ArkBundle\Command\ReindexCommand;
use Survos\ArkBundle\Command\ReportCommand;
use Survos\ArkBundle\Command\ResolveCommand;
use Survos\ArkBundle\Command\ValidateCommand;
use Survos\ArkBundle\Controller\ArkRedirectController;
use Survos\ArkBundle\Doctrine\ArkDoctrineListener;
use Survos\ArkBundle\Repository\ArkBindingRepository;
use Survos\ArkBundle\Service\ArkRegistry;
use Survos\ArkBundle\Service\NoidMinterService;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('survos_ark.minter', NoidMinterService::class)
            ->args([
                '%survos_ark.naan%',
                '%survos_ark.shoulder%',
                '%survos_ark.template%',
                '%survos_ark.db_type%',
                '%survos_ark.db_path%',
            ])
            ->tag('kernel.reset', ['method' => 'close'])

        ->set('survos_ark.registry', ArkRegistry::class)
            ->args([
                service('survos_ark.minter'),
                '%survos_ark.resolver_base_url%',
                service('doctrine')->ignoreOnInvalid(),
                service(ArkBindingRepository::class)->ignoreOnInvalid(),
                service('doctrine.orm.entity_manager')->ignoreOnInvalid(),
            ])

        ->set('survos_ark.doctrine_listener', ArkDoctrineListener::class)
            ->args([
                service('survos_ark.minter'),
                '%survos_ark.auto_mint%',
            ])
            ->tag('doctrine.event_listener', ['event' => 'prePersist'])
            ->tag('doctrine.event_listener', ['event' => 'preUpdate'])

        ->set('survos_ark.controller.redirect', ArkRedirectController::class)
            ->args([
                service('survos_ark.registry'),
                '%survos_ark.naan%',
                '%survos_ark.n2t_resolve%',
            ])

        ->set(MintCommand::class)
            ->args([service('survos_ark.minter')])
            ->tag('console.command')

        ->set(BindCommand::class)
            ->args([service('survos_ark.minter')])
            ->tag('console.command')

        ->set(ResolveCommand::class)
            ->args([service('survos_ark.minter')])
            ->tag('console.command')

        ->set(ValidateCommand::class)
            ->args([service('survos_ark.minter')])
            ->tag('console.command')

        ->set(BulkMintCommand::class)
            ->args([
                service('survos_ark.minter'),
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
                service('survos_ark.minter'),
                service('doctrine')->ignoreOnInvalid(),
            ])
            ->tag('console.command')

        ->set(ArkExportCommand::class)
            ->args([service(ArkBindingRepository::class)->ignoreOnInvalid()])
            ->tag('console.command')

        ->set(ArkImportCommand::class)
            ->args([service('doctrine.orm.entity_manager')->ignoreOnInvalid()])
            ->tag('console.command')
    ;
};
