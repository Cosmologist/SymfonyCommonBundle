<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Bundle configuration
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('symfony_common');

        $rootNode
            ->children()
                ->arrayNode('twig')
                    ->children()
                        ->arrayNode('php_extension')
                            ->children()
                                ->arrayNode('filters')
                                    ->prototype('variable')
                                    ->end()
                                ->end()
                                ->arrayNode('functions')
                                    ->prototype('variable')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
