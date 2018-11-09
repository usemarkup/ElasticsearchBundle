<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\DataCollector;

use Markup\Json\Encoder;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * A PSR-3 logger that can be set within the Elasticsearch client object as a tracer, and provide output
 * to a Symfony data collector for the web profiler.
 */
class TracerLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var ElasticDataCollector
     */
    private $collector;

    public function __construct(ElasticDataCollector $collector)
    {
        $this->collector = $collector;
    }

    public function log($level, $message, array $context = [])
    {
        if ($level === LogLevel::INFO && substr($message, 0, 4) === 'curl') {
            //capture last part of curl command, which is payload as JSON
            $payloadJson = substr($message, strpos($message, '-d ')+4, -1);
            $this->collector->addRequest(Encoder::decode($payloadJson, true));

            return;
        }

        //assuming this is response...
        $this->collector->addResponse($context);
    }
}
