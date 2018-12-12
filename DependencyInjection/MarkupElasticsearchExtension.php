<?php

namespace Markup\ElasticsearchBundle\DependencyInjection;

use Markup\ElasticsearchBundle\ClientFactory;
use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Markup\ElasticsearchBundle\ServiceLocator;
use Markup\ElasticsearchBundle\Twig\KibanaLinkExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Loads configuration for bundle.
 */
class MarkupElasticsearchExtension extends Extension
{
    use AddServiceLocatorArgumentTrait;

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
        $this->configureGeneralServices($config, $container);
        $this->configureTracerLogger($container);
        $this->registerKibanaServices($config['kibana'], $container);
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

    private function configureGeneralServices(array $config, ContainerBuilder $container)
    {
        $nodesForServices = ['logger'];
        $locator = $container->findDefinition(ServiceLocator::class);
        foreach ($nodesForServices as $nodeForService) {
            $this->registerServiceToLocator($nodeForService, $config[$nodeForService], $locator);
        }
    }

    private function configureTracerLogger(ContainerBuilder $container)
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

    private function registerKibanaServices(array $config, ContainerBuilder $container)
    {
        if (!$container->getParameter('kernel.debug')) {
            $container->removeDefinition(KibanaLinkExtension::class);

            return;
        }
        $definition = $container->findDefinition(KibanaLinkExtension::class);
        $definition->setArgument('$kibanaHost', $config['host']);
        $definition->setArgument('$shouldLinkToKibana', $config['should_link_from_profiler']);
        $definition->addTag('twig.extension');
    }
}
