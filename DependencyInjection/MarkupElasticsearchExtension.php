<?php

namespace Markup\ElasticsearchBundle\DependencyInjection;

use Composer\CaBundle\CaBundle;
use Elasticsearch\ClientBuilder;
use Markup\ElasticsearchBundle\ClientFactory;
use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Markup\ElasticsearchBundle\DataCollector\TracerLogger;
use Markup\ElasticsearchBundle\Provider\ConnectionFactoryProvider;
use Markup\ElasticsearchBundle\Provider\ConnectionPoolProvider;
use Markup\ElasticsearchBundle\Provider\HandlerProvider;
use Markup\ElasticsearchBundle\Provider\SelectorProvider;
use Markup\ElasticsearchBundle\Provider\SerializerProvider;
use Markup\ElasticsearchBundle\ServiceLocator;
use Markup\ElasticsearchBundle\Twig\KibanaLinkExtension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
        $this->configureCustomHandlers($config, $container);
        $this->configureCustomConnectionFactories($config, $container);
        $this->configureEndpointClosure($config['endpoint_closure'], $container);
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
        $knownHandlerNames = array_keys($config['custom_handlers']);
        $knownConnectionFactoryNames = array_keys($config['custom_connection_factories']);
        foreach ($config['clients'] as $clientName => $clientConfig) {
            $this->ensureConnectionPoolResolvable($clientConfig['connection_pool'], $knownConnectionPoolNames);
            $this->ensureConnectionSelectorResolvable($clientConfig['connection_selector'], $knownConnectionSelectorNames);
            $this->ensureSerializerResolvable($clientConfig['serializer'], $knownSerializerNames);
            $this->ensureHandlerResolvable($clientConfig['handler'], $knownHandlerNames);
            $this->ensureConnectionFactoriesResolvable($clientConfig['connection_factory'], $knownConnectionFactoryNames);
            $client = (new Definition(\Elasticsearch\Client::class))
                ->setFactory([new Reference(ClientFactory::class), 'create'])
                ->setArguments([
                    $clientConfig['nodes'],
                    $clientConfig['connection_pool'],
                    $clientConfig['connection_selector'],
                    $clientConfig['serializer'],
                    $clientConfig['connection_factory'],
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
        if (!$this->hasBundle($container, 'WebProfilerBundle')) {
            $container->removeDefinition(TracerLogger::class);

            return;
        }
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
        $this->configureCustomServiceProviders(
            $config,
            $container,
            ConnectionPoolProvider::class,
            'connection_pool',
            'custom_connection_pools',
            '$customPoolLocator'
        );
    }

    private function configureCustomConnectionSelectors(array $config, ContainerBuilder $container): void
    {
        $this->configureCustomServiceProviders(
            $config,
            $container,
            SelectorProvider::class,
            'connection_selector',
            'custom_connection_selectors',
            '$customSelectorLocator'
        );
    }

    private function configureCustomSerializers(array $config, ContainerBuilder $container): void
    {
        $this->configureCustomServiceProviders(
            $config,
            $container,
            SerializerProvider::class,
            'serializer',
            'custom_serializers',
            '$customSerializerLocator'
        );
    }

    private function configureCustomConnectionFactories(array $config, ContainerBuilder $container): void
    {
        $this->configureCustomServiceProviders(
            $config,
            $container,
            ConnectionFactoryProvider::class,
            'connection_factory',
            'custom_connection_factories',
            '$customConnectionFactoryLocator'
        );
    }

    private function configureCustomHandlers(array $config, ContainerBuilder $container): void
    {
        $inbuiltHandlers = $this->getKnownHandlerNames();
        $handlerFactoryIds = [];
        foreach ($inbuiltHandlers as $inbuiltHandler) {
            $handlerFactoryIds[$inbuiltHandler] = sprintf('markup_elasticsearch.handler_factory.%s', $inbuiltHandler);
            $container->setDefinition($handlerFactoryIds[$inbuiltHandler], $this->makeHandlerFactoryDefinition($inbuiltHandler.'Handler'));
        }
        $this->configureCustomServiceProviders(
            $config,
            $container,
            HandlerProvider::class,
            'handler',
            'custom_handlers',
            '$customHandlerLocator',
            $handlerFactoryIds
        );
    }

    private function getKnownHandlerNames(): array
    {
        return ['default', 'single', 'multi'];
    }

    private function makeHandlerFactoryDefinition(string $builderMethod)
    {
        return (new Definition(\Closure::class))
            ->setFactory([ClientBuilder::class, $builderMethod])
            ->setPublic(false);
    }

    private function configureCustomServiceProviders(
        array $config,
        ContainerBuilder $container,
        string $providerClass,
        string $providerServicePrefix,
        string $customConfigNode,
        string $constructorArg,
        array $additionalLocatorServices = []
    ): void {
        $provider = $container->findDefinition($providerClass);
        $serviceLocator = (new Definition(SymfonyServiceLocator::class))
            ->addTag('container.service_locator')
            ->setArguments([
                array_map(
                    function (string $serviceId) {
                        return new Reference($serviceId);
                    },
                    array_merge($config[$customConfigNode], $additionalLocatorServices)
                )
            ])
            ->setPublic(false);
        $serviceLocatorId = sprintf('markup_elasticsearch.custom_%s.locator', $providerServicePrefix);
        $container->setDefinition($serviceLocatorId, $serviceLocator);
        $provider->setArgument(
            $constructorArg,
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

    private function configureEndpointClosure(?string $endpointClosureService, ContainerBuilder $container)
    {
        $container->findDefinition(ClientFactory::class)
            ->setArgument(
                '$endpointClosure',
                ($endpointClosureService) ? new Reference($endpointClosureService) : null
            );

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

    private function ensureHandlerResolvable(?string $nameToTest, array $knownNames): void
    {
        $this->ensureServiceNamesResolvable(
            $nameToTest,
            array_fill_keys($this->getKnownHandlerNames(), true),
            $knownNames,
            'The handler name "%s" is not known. Did you forget to register a custom handler?'
        );
    }

    private function ensureConnectionFactoriesResolvable(?string $nameToTest, array $knownNames): void
    {
        $this->ensureServiceNamesResolvable(
            $nameToTest,
            [],
            $knownNames,
            'The connection factory name "%s" is not known. Did you forget to register a custom connection factory?'
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

    private function hasBundle(ContainerInterface $container, $bundle): bool
    {
        if (!$container->hasParameter('kernel.bundles')) {
            return false;
        }

        $bundles = $container->getParameter('kernel.bundles');

        return isset($bundles[$bundle]);
    }
}
