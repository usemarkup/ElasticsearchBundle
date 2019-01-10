<?php

namespace Markup\ElasticsearchBundle\DependencyInjection;

use Composer\CaBundle\CaBundle;
use Markup\ElasticsearchBundle\ClientFactory;
use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Markup\ElasticsearchBundle\Provider\ConnectionPoolProvider;
use Markup\ElasticsearchBundle\Provider\SelectorProvider;
use Markup\ElasticsearchBundle\Provider\SerializerProvider;
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
        $this->configureCustomConnectionSelectors($config, $container);
        $this->configureCustomSerializers($config, $container);
        $this->configureGeneralServices($config, $container);
        $this->configureGeneralSettings($config, $container);
        $this->configureTracerLogger($container);
        $this->registerKibanaServices($config['kibana'], $container);
    }

    private function configureClients(array $config, ContainerBuilder $container)
    {
        $knownConnectionPoolNames = array_keys($config['custom_connection_pools']);
        $knownConnectionSelectorNames = array_keys($config['custom_connection_selectors']);
        $knownSerializerNames = array_keys($config['custom_serializers']);
        foreach ($config['clients'] as $clientName => $clientConfig) {
            $this->ensureConnectionPoolResolvable($clientConfig['connection_pool'], $knownConnectionPoolNames);
            $this->ensureConnectionSelectorResolvable($clientConfig['connection_selector'], $knownConnectionSelectorNames);
            $this->ensureSerializerResolvable($clientConfig['serializer'], $knownSerializerNames);
            $client = (new Definition(\Elasticsearch\Client::class))
                ->setFactory([new Reference(ClientFactory::class), 'create'])
                ->setArguments([
                    $clientConfig['nodes'],
                    $clientConfig['connection_pool'],
                    $clientConfig['connection_selector'],
                    $clientConfig['serializer'],
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

    private function configureCustomConnectionPools(array $config, ContainerBuilder $container): void
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
            '$customPoolLocator',
            new Reference($serviceLocatorId)
        );
    }

    private function configureCustomConnectionSelectors(array $config, ContainerBuilder $container): void
    {
        $selectorProvider = $container->findDefinition(SelectorProvider::class);
        $serviceLocator = (new Definition(SymfonyServiceLocator::class))
            ->addTag('container.service_locator')
            ->setArguments([
                array_map(
                    function (string $selectorServiceId) {
                        return new Reference($selectorServiceId);
                    },
                    $config['custom_connection_selectors']
                )
            ])
            ->setPublic(false);
        $serviceLocatorId = 'markup_elasticsearch.custom_connection_selector.locator';
        $container->setDefinition($serviceLocatorId, $serviceLocator);
        $selectorProvider->setArgument(
            '$customSelectorLocator',
            new Reference($serviceLocatorId)
        );
    }

    private function configureCustomSerializers(array $config, ContainerBuilder $container): void
    {
        $serializerProvider = $container->findDefinition(SerializerProvider::class);
        $serviceLocator = (new Definition(SymfonyServiceLocator::class))
            ->addTag('container.service_locator')
            ->setArguments([
                array_map(
                    function (string $serializerServiceId) {
                        return new Reference($serializerServiceId);
                    },
                    $config['custom_serializers']
                )
            ])
            ->setPublic(false);
        $serviceLocatorId = 'markup.elasticsearch.custom_serializer.locator';
        $container->setDefinition($serviceLocatorId, $serviceLocator);
        $serializerProvider->setArgument(
            '$customSerializerLocator',
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
        $this->ensureServiceNamesResolvable(
            $nameToTest,
            ConnectionPoolProvider::KNOWN_POOLS,
            $knownNames,
            'The connection pool name "%s" is not known. Did you forget to register a custom connection pool service?'
        );
    }

    /**
     * @throws \LogicException if a connection selector name is used that is not defined
     */
    private function ensureConnectionSelectorResolvable(?string $nameToTest, array $knownNames): void
    {
        $this->ensureServiceNamesResolvable(
            $nameToTest,
            SelectorProvider::KNOWN_SELECTORS,
            $knownNames,
            'The connection selector name "%s" is not known. Did you forget to register a custom connection selector?'
        );
    }

    private function ensureSerializerResolvable(?string $nameToTest, array $knownNames): void
    {
        $this->ensureServiceNamesResolvable(
            $nameToTest,
            SerializerProvider::KNOWN_SERIALIZERS,
            $knownNames,
            'The serializer name "%s" is not known. Did you forget to register a custom serializer?'
        );
    }

    private function ensureServiceNamesResolvable(
        ?string $nameToTest,
        array $inBuiltServices,
        array $customNames,
        string $messageTemplate
    ): void {
        if ($nameToTest === null) {
            return;
        }
        if (!in_array($nameToTest, array_keys($inBuiltServices)) && !in_array($nameToTest, $customNames)) {
            throw new \LogicException(sprintf($messageTemplate, $nameToTest));
        }
    }
}
