<?php

namespace Markup\ElasticsearchBundle\DependencyInjection;

use Composer\CaBundle\CaBundle;
use Markup\ElasticsearchBundle\ClientFactory;
use Markup\ElasticsearchBundle\ConnectionPool\ConnectionPoolProvider;
use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Markup\ElasticsearchBundle\ServiceLocator;
use Markup\ElasticsearchBundle\Twig\KibanaLinkExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator as SymfonyServiceLocator;
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
        $this->configureCustomConnectionPools($config, $container);
        $this->configureGeneralServices($config, $container);
        $this->configureGeneralSettings($config, $container);
        $this->configureTracerLogger($container);
        $this->registerKibanaServices($config['kibana'], $container);
    }

    private function configureClients(array $config, ContainerBuilder $container)
    {
        $knownConnectionPoolNames = array_keys($config['custom_connection_pools']);
        foreach ($config['clients'] as $clientName => $clientConfig) {
            $this->ensureConnectionPoolResolvable($clientConfig['connection_pool'], $knownConnectionPoolNames);
            $client = (new Definition(\Elasticsearch\Client::class))
                ->setFactory([new Reference(ClientFactory::class), 'create'])
                ->setArguments([
                    $clientConfig['nodes'],
                    $clientConfig['connection_pool'],
                    $clientConfig['ssl_cert']
                ])
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

    private function configureGeneralSettings(array $config, ContainerBuilder $container)
    {
        $clientFactory = $container->findDefinition(ClientFactory::class);
        if (isset($config['retries'])) {
            $clientFactory->setArgument('$retries', $config['retries']);
        }
        $clientFactory->setArgument('$useCaBundle', class_exists(CaBundle::class));
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

    private function configureCustomConnectionPools(array $config, ContainerBuilder $container)
    {
        $poolProvider = $container->findDefinition(ConnectionPoolProvider::class);
        $serviceLocator = (new Definition(SymfonyServiceLocator::class))
            ->addTag('container.service_locator')
            ->setArguments([
                array_map(
                    function (string $poolServiceId) {
                        return new Reference($poolServiceId);
                    },
                    $config['custom_connection_pools']
                )
            ])
            ->setPublic(false);
        $serviceLocatorId = 'markup_elasticsearch.custom_connection_pool.locator';
        $container->setDefinition($serviceLocatorId, $serviceLocator);
        $poolProvider->setArgument(
            '$customServiceLocator',
            new Reference($serviceLocatorId)
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

    /**
     * @throws \LogicException if a connection pool name is used that is not defined
     */
    private function ensureConnectionPoolResolvable(?string $nameToTest, array $knownNames): void
    {
        if ($nameToTest === null) {
            return;
        }
        if (!in_array($nameToTest, array_keys(ConnectionPoolProvider::KNOWN_POOLS)) && !in_array($nameToTest, $knownNames)) {
            throw new \LogicException(
                sprintf(
                    'The connection pool name "%s" is not known. Did you forget to register a custom connection pool service?',
                    $nameToTest
                )
            );
        }
    }
}
