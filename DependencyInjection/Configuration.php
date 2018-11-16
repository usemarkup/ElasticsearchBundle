<?php

namespace Markup\ElasticsearchBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('markup_elasticsearch');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('clients')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('nodes')
                                ->beforeNormalization()
                                    ->castToArray()
                                ->end()
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('host')
                                            ->defaultValue('localhost')
                                        ->end()
                                        ->integerNode('port')
                                            ->defaultValue(9200)
                                        ->end()
                                        ->scalarNode('scheme')
                                            ->defaultValue('http')
                                        ->end()
                                        ->scalarNode('user')
                                            ->defaultNull()
                                        ->end()
                                        ->scalarNode('pass')
                                            ->defaultNull()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('logger')
                    ->defaultValue('logger')
                ->end()
                ->arrayNode('kibana')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('host')
                            ->defaultValue('http://localhost:5601')
                        ->end()
                        ->booleanNode('should_link_from_profiler')
                            ->defaultFalse()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
