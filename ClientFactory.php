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

    public function create(): Client
    {
        return ClientBuilder::create()
            ->setLogger($this->logger)
            ->setTracer($this->tracer)
            ->build();
    }
}
