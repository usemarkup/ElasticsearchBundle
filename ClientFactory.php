<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle;

use Composer\CaBundle\CaBundle;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Markup\ElasticsearchBundle\ConnectionPool\ConnectionPoolProvider;
use Markup\ElasticsearchBundle\DataCollector\TracerLogger;
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
        ?TracerLogger $tracer = null,
        ?int $retries = null,
        ?bool $useCaBundle = false
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->connectionPoolProvider = $connectionPoolProvider;
        $this->tracer = $tracer ?? new NullLogger();
        $this->retries = $retries;
        $this->useCaBundle = (bool) $useCaBundle;
    }

    public function create(
        array $hosts = [],
        ?string $connectionPool = null,
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
            $clientBuilder->setConnectionPool($this->connectionPoolProvider->retrieveConnectionPool($connectionPool));
        }

        return $clientBuilder->build();
    }
}
