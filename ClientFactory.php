<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle;

use Composer\CaBundle\CaBundle;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Markup\ElasticsearchBundle\DataCollector\TracerLogger;
use Markup\ElasticsearchBundle\Provider\ConnectionPoolProvider;
use Markup\ElasticsearchBundle\Provider\HandlerProvider;
use Markup\ElasticsearchBundle\Provider\SelectorProvider;
use Markup\ElasticsearchBundle\Provider\SerializerProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ClientFactory
{
    /**
     * @var ContainerInterface
     */
    private $serviceLocator;

    /**
     * @var ConnectionPoolProvider
     */
    private $connectionPoolProvider;

    /**
     * @var SelectorProvider
     */
    private $connectionSelectorProvider;

    /**
     * @var SerializerProvider
     */
    private $serializerProvider;

    /**
     * @var HandlerProvider
     */
    private $handlerProvider;

    /**
     * @var LoggerInterface
     */
    private $tracer;

    /**
     * @var ?int
     */
    private $retries;

    /**
     * @var bool
     */
    private $useCaBundle;

    public function __construct(
        ServiceLocator $serviceLocator,
        ConnectionPoolProvider $connectionPoolProvider,
        SelectorProvider $connectionSelectorProvider,
        SerializerProvider $serializerProvider,
        HandlerProvider $handlerProvider,
        ?TracerLogger $tracer = null,
        ?int $retries = null,
        ?bool $useCaBundle = false
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->connectionPoolProvider = $connectionPoolProvider;
        $this->connectionSelectorProvider = $connectionSelectorProvider;
        $this->serializerProvider = $serializerProvider;
        $this->handlerProvider = $handlerProvider;
        $this->tracer = $tracer ?? new NullLogger();
        $this->retries = $retries;
        $this->useCaBundle = (bool) $useCaBundle;
    }

    public function create(
        array $hosts = [],
        ?string $connectionPool = null,
        ?string $connectionSelector = null,
        ?string $serializer = null,
        ?string $handler = null,
        ?string $sslCertFile = null): Client
    {
        $clientBuilder = ClientBuilder::create()
            ->setTracer($this->tracer);
        if ($this->serviceLocator->has('logger')) {
            $clientBuilder->setLogger($this->serviceLocator->get('logger'));
        }
        if (count($hosts) > 0) {
            $clientBuilder->setHosts($hosts);
        }
        if ($this->retries !== null) {
            $clientBuilder->setRetries($this->retries);
        }
        if ($this->useCaBundle) {
            $cert = $sslCertFile ?? CaBundle::getBundledCaBundlePath();
            $clientBuilder->setSSLVerification($cert);
        }
        if (null !== $connectionPool) {
            $clientBuilder->setConnectionPool($this->connectionPoolProvider->retrieveConnectionPool($connectionPool), []);
        }
        if (null !== $connectionSelector) {
            $clientBuilder->setSelector($this->connectionSelectorProvider->retrieveSelector($connectionSelector));
        }
        if (null !== $serializer) {
            $clientBuilder->setSerializer($this->serializerProvider->retrieveSerializer($serializer));
        }
        if (null !== $handler) {
            $clientBuilder->setHandler($this->handlerProvider->retrieveHandler($handler));
        }

        return $clientBuilder->build();
    }
}
