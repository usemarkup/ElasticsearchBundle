<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle;

use Composer\CaBundle\CaBundle;
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

    /**
     * @var bool
     */
    private $useCaBundle;

    public function __construct(
        ServiceLocator $serviceLocator,
        ?TracerLogger $tracer = null,
        ?int $retries = null,
        ?bool $useCaBundle = false
    ) {
        $this->serviceLocator = $serviceLocator;
        $this->tracer = $tracer ?? new NullLogger();
        $this->retries = $retries;
        $this->useCaBundle = (bool) $useCaBundle;
    }

    public function create(array $hosts = [], ?string $sslCertFile = null): Client
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

        return $clientBuilder->build();
    }
}
