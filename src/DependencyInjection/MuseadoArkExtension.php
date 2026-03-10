<?php

declare(strict_types=1);

namespace Museado\ArkBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class MuseadoArkExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('museado_ark.naan', $config['naan']);
        $container->setParameter('museado_ark.shoulder', $config['shoulder']);
        $container->setParameter('museado_ark.template', $config['template']);
        $container->setParameter('museado_ark.resolver_base_url', $config['resolver_base_url']);
        $container->setParameter('museado_ark.local_path', $config['local_path']);
        $container->setParameter('museado_ark.db_type', $config['db_type']);
        $container->setParameter('museado_ark.db_path', $config['db_path']);
        $container->setParameter('museado_ark.auto_mint', $config['auto_mint']);
        $container->setParameter('museado_ark.n2t_resolve', $config['n2t_resolve']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
