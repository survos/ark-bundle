<?php

declare(strict_types=1);

namespace Survos\ArkBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SurvosArkBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('naan')->defaultNull()->end()
                ->scalarNode('shoulder')->defaultValue('')->end()
                ->scalarNode('template')->defaultValue('fk.reedeeedk')->end()
                ->scalarNode('resolver_base_url')->defaultNull()->end()
                ->scalarNode('local_path')->defaultValue('/ark')->end()
                ->scalarNode('db_type')->defaultValue('sqlite')->end()
                ->scalarNode('db_path')->defaultValue('%kernel.project_dir%/var/noid')->end()
                ->booleanNode('auto_mint')->defaultTrue()->end()
                ->booleanNode('n2t_resolve')->defaultFalse()->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('survos_ark.naan', $config['naan']);
        $builder->setParameter('survos_ark.shoulder', $config['shoulder']);
        $builder->setParameter('survos_ark.template', $config['template']);
        $builder->setParameter('survos_ark.resolver_base_url', $config['resolver_base_url']);
        $builder->setParameter('survos_ark.local_path', $config['local_path']);
        $builder->setParameter('survos_ark.db_type', $config['db_type']);
        $builder->setParameter('survos_ark.db_path', $config['db_path']);
        $builder->setParameter('survos_ark.auto_mint', $config['auto_mint']);
        $builder->setParameter('survos_ark.n2t_resolve', $config['n2t_resolve']);

        $container->import(__DIR__ . '/Resources/config/services.php');
    }
}
