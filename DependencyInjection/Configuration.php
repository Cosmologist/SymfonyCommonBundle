<?php

namespace Cosmologist\Bundle\SymfonyCommonBundle\DependencyInjection;

use Cosmologist\Bundle\SymfonyCommonBundle\Type\CallableType;
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
        $twigFunctionsNormalizer = function ($list) {
            $normalized = [];

            foreach ($list as $item) {
                if (is_string($item)) {
                    $normalized[$item] = $item;
                } else {
                    $normalized += $item;
                }
            }

            return $normalized;
        };

        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root('symfony_common');

        $rootNode
            ->children()
                ->arrayNode('external_config')
                    ->prototype('array')
                        ->prototype('variable')->end()
                    ->end()
                ->end()

                ->arrayNode('expression_language')
                    ->children()
                        ->arrayNode('presets')
                            ->prototype('array')
                                ->prototype('variable')
                                    ->info('Function. Supported formats: "foo", "FooStaticClass::bar", "FooDIService::bar").')
                                    ->validate()
                                        ->ifTrue(function ($value) {
                                            return !CallableType::validate($value);
                                        })
                                        ->thenInvalid('Invalid callable format')
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('twig')
                    ->children()
                        ->arrayNode('php_extension')
                            ->children()
                                ->arrayNode('filters')
                                    ->beforeNormalization()
                                        ->always($twigFunctionsNormalizer)
                                    ->end()

                                    ->prototype('variable')
                                    ->end()
                                ->end()
                                ->arrayNode('functions')
                                        ->beforeNormalization()
                                            ->always($twigFunctionsNormalizer)
                                        ->end()

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
