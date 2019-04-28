<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection;

use Cosmologist\Bundle\SymfonyCommonBundle\ExpressionLanguage\ExpressionLanguageRegistry;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class SymfonyCommonExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config/services'));
        $loader->load('bridge.yml');
        $loader->load('doctrine.yml');
        $loader->load('routing.yml');
        $loader->load('security.yml');
        $loader->load('twig.yml');

        $container->setParameter('symfony_common.external_config', $config['external_config']);

        $container->setParameter('symfony_common.twig.php_extension.filters', $config['twig']['php_extension']['filters']);
        $container->setParameter('symfony_common.twig.php_extension.functions', $config['twig']['php_extension']['functions']);

        $this->initExpressionLanguageRegistry($container, $config['expression_language']['presets'] ?? []);

    }

    /**
     * @param ContainerBuilder $container
     * @param array            $presets
     */
    private function initExpressionLanguageRegistry(ContainerBuilder $container, array $presets)
    {
        $definition = $container->register(ExpressionLanguageRegistry::class);
        foreach ($presets as $name => $functions) {
            $definition->addMethodCall('set', [$name, array_combine($functions, $functions)]);
        }
    }
}
