<?php

declare(strict_types=1);

namespace Survos\ArkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class SurvosArkExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('survos_ark.naan', $config['naan']);
        $container->setParameter('survos_ark.shoulder', $config['shoulder']);
        $container->setParameter('survos_ark.template', $config['template']);
        $container->setParameter('survos_ark.resolver_base_url', $config['resolver_base_url']);
        $container->setParameter('survos_ark.local_path', $config['local_path']);
        $container->setParameter('survos_ark.db_type', $config['db_type']);
        $container->setParameter('survos_ark.db_path', $config['db_path']);
        $container->setParameter('survos_ark.auto_mint', $config['auto_mint']);
        $container->setParameter('survos_ark.n2t_resolve', $config['n2t_resolve']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
