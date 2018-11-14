<?php

namespace Markup\ElasticsearchBundle\DependencyInjection;

use Markup\ElasticsearchBundle\ClientFactory;
use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class MarkupElasticsearchExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->configureClients($config, $container);
        $this->configureTracerLogger($config, $container);
    }

    private function configureClients(array $config, ContainerBuilder $container)
    {
        foreach ($config['clients'] as $clientName => $clientConfig) {
            $client = (new Definition(\Elasticsearch\Client::class))
                ->setFactory([new Reference(ClientFactory::class), 'create'])
                ->setArguments([$clientConfig['nodes']])
                ->setPrivate(true);
            $container->setDefinition(sprintf('markup_elasticsearch.client.%s', $clientName), $client);
        }
    }

    private function configureTracerLogger(array $config, ContainerBuilder $container)
    {
        $collector = $container->findDefinition(ElasticDataCollector::class);
        $collector->addTag(
            'data_collector',
            [
                'id' => ElasticDataCollector::NAME,
                'template' => '@MarkupElasticsearch/Collector/elastic.html.twig',
            ]
        );
    }
}
