<?php

declare(strict_types=1);

namespace Museado\ArkBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('museado_ark');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('naan')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('shoulder')->defaultValue('')->end()
                ->scalarNode('template')->defaultValue('fk.reedeeedk')->end()
                ->scalarNode('resolver_base_url')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('local_path')->defaultValue('/ark')->end()
                ->scalarNode('db_type')->defaultValue('lmdb')->end()
                ->scalarNode('db_path')->defaultValue('%kernel.var_dir%/ark')->end()
                ->booleanNode('auto_mint')->defaultTrue()->end()
                ->booleanNode('n2t_resolve')->defaultFalse()->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
