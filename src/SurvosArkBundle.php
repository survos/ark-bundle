<?php

declare(strict_types=1);

namespace Survos\ArkBundle;

use Survos\ArkBundle\Controller\ArkRedirectController;
use Survos\ArkBundle\Doctrine\ArkDoctrineListener;
use Survos\ArkBundle\Repository\ArkBindingRepository;
use Survos\ArkBundle\Service\ArkRegistry;
use Survos\ArkBundle\Service\ArkUlidCodec;
use Survos\ArkBundle\Service\NoidMinterService;
use Survos\ArkBundle\Twig\ArkTwigRuntime;
use Survos\CoreBundle\Traits\HasConfigurableRoutes;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class SurvosArkBundle extends AbstractBundle
{
    use HasConfigurableRoutes;

    public function configure(DefinitionConfigurator $definition): void
    {
        $children = $definition->rootNode()->children();
        $this->addRouteOptions($children, '/ark');
        $children
            ->scalarNode('naan')->defaultNull()->end()
            ->scalarNode('shoulder')->defaultValue('')->end()
            ->scalarNode('template')->defaultValue('fk.reedeeedk')->end()
            ->scalarNode('resolver_base_url')->defaultNull()->end()
            ->scalarNode('local_path')->defaultValue('/ark')->end()
            ->scalarNode('db_type')->defaultValue('sqlite')->end()
            ->scalarNode('db_path')->defaultValue('%kernel.project_dir%/var/noid')->end()
            ->booleanNode('auto_mint')->defaultTrue()->end()
            ->booleanNode('n2t_resolve')->defaultFalse()->end()
        ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $this->addRouteLoaderCompilerPass($container);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->captureRouteConfig($config);
        $this->registerRouteLoader($builder);

        $builder->setParameter('survos_ark.naan', $config['naan']);
        $builder->setParameter('survos_ark.shoulder', $config['shoulder']);
        $builder->setParameter('survos_ark.template', $config['template']);
        $builder->setParameter('survos_ark.resolver_base_url', $config['resolver_base_url']);
        $builder->setParameter('survos_ark.local_path', $config['local_path']);
        $builder->setParameter('survos_ark.db_type', $config['db_type']);
        $builder->setParameter('survos_ark.db_path', $config['db_path']);
        $builder->setParameter('survos_ark.auto_mint', $config['auto_mint']);
        $builder->setParameter('survos_ark.n2t_resolve', $config['n2t_resolve']);

        $services = $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure();

        // Core services — explicit args because constructors mix scalar params and
        // optional services. Aliased to FQCN so commands can autowire by type.
        $services->set(NoidMinterService::class)
            ->args([
                '%survos_ark.naan%',
                '%survos_ark.shoulder%',
                '%survos_ark.template%',
                '%survos_ark.db_type%',
                '%survos_ark.db_path%',
            ])
            ->tag('kernel.reset', ['method' => 'close']);
        $services->alias('survos_ark.minter', NoidMinterService::class);

        $services->set(ArkRegistry::class)
            ->args([
                service(NoidMinterService::class),
                '%survos_ark.resolver_base_url%',
                service('doctrine')->ignoreOnInvalid(),
                service(ArkBindingRepository::class)->ignoreOnInvalid(),
                service('doctrine.orm.entity_manager')->ignoreOnInvalid(),
            ]);
        $services->alias('survos_ark.registry', ArkRegistry::class);

        $services->set(ArkUlidCodec::class)
            ->args([
                '%survos_ark.naan%',
                '%survos_ark.resolver_base_url%',
                '%survos_ark.local_path%',
            ]);
        $services->alias('survos_ark.ulid_codec', ArkUlidCodec::class);

        $services->set(ArkTwigRuntime::class)
            ->args([service(ArkUlidCodec::class)]);

        // #[AsDoctrineListener(event: ...)] on the class registers the events
        // automatically — autoconfigure picks them up. No explicit tag needed.
        $services->set(ArkDoctrineListener::class)
            ->args([
                service(NoidMinterService::class),
                '%survos_ark.auto_mint%',
            ]);

        $services->set(ArkRedirectController::class)
            ->args([
                service(ArkRegistry::class),
                '%survos_ark.naan%',
                '%survos_ark.n2t_resolve%',
            ])
            ->public();
        $services->alias('survos_ark.controller.redirect', ArkRedirectController::class);

        // Auto-register every command in src/Command/. autoconfigure adds the
        // console.command tag from #[AsCommand]; autowire fills constructor args
        // from the FQCN-keyed services registered above.
        $services->load('Survos\\ArkBundle\\Command\\', __DIR__ . '/Command/');

        // Two commands have non-nullable Doctrine deps that fail autowiring
        // when the host app doesn't map ArkBundle entities in doctrine.yaml.
        // Override with `ignoreOnInvalid()` so the bundle stays installable
        // without requiring app-level Doctrine config.
        $services->set(\Survos\ArkBundle\Command\ArkExportCommand::class)
            ->args([service(\Survos\ArkBundle\Repository\ArkBindingRepository::class)->ignoreOnInvalid()]);

        $services->set(\Survos\ArkBundle\Command\ArkImportCommand::class)
            ->args([service('doctrine.orm.entity_manager')->ignoreOnInvalid()]);
    }
}
