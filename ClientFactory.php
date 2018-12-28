<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
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
     * @var LoggerInterface
     */
    private $tracer;

    /**
     * @var ?int
     */
    private $retries;

    public function __construct(
        ServiceLocator $serviceLocator,
        ?TracerLogger $tracer = null,
        ?int $retries = null
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->tracer = $tracer ?? new NullLogger();
        $this->retries = $retries;
    }

    public function create(array $hosts = []): Client
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

        return $clientBuilder->build();
    }
}
