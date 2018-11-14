<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Markup\ElasticsearchBundle\DataCollector\TracerLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ClientFactory
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var LoggerInterface
     */
    private $tracer;

    public function __construct(?LoggerInterface $logger = null, ?TracerLogger $tracer = null)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->tracer = $tracer ?? new NullLogger();
    }

    public function create(array $hosts = []): Client
    {
        $clientBuilder = ClientBuilder::create()
            ->setLogger($this->logger)
            ->setTracer($this->tracer);
        if (count($hosts) > 0) {
            $clientBuilder->setHosts($hosts);
        }

        return $clientBuilder->build();
    }
}
