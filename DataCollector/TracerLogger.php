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
 *
 * NB. As there is no explicit association at the current time between requests and responses in
 * terms of how the Elasticsearch SDK performs logging, responses here are associated with requests on a
 * first in, first out basis. This means that, for now, multiple asynchronous requests could result in
 * responses logged out of order.
 */
class TracerLogger implements LoggerInterface
{
    use LoggerTrait;

    /**
     * @var ElasticDataCollector
     */
    private $collector;

    /**
     * @var array - A first in, first out list of interaction IDs.
     */
    private $interactionIds;

    public function __construct(ElasticDataCollector $collector)
    {
        $this->collector = $collector;
        $this->interactionIds = [];
    }

    public function log($level, $message, array $context = [])
    {
        $interactionId = bin2hex(random_bytes(5));

        if ($level === LogLevel::INFO && substr($message, 0, 4) === 'curl') {
            //capture last part of curl command, which is payload as JSON
            $messageBody = substr($message, strpos($message, '-d ')+4, -1);
            $payloadJsonStrings = explode("\n", $messageBody);
            $this->collector->addRequest(
                array_map(
                    function (string $json) {
                        try {
                            $decoded = Encoder::decode($json, true);
                        } catch (\JsonException $e) {
                            $decoded = null;
                        }

                        return $decoded;
                    },
                    $payloadJsonStrings
                ),
                $interactionId
            );
            $this->addToInteractionIds($interactionId);

            $this->collector->addRequestBody($messageBody, $interactionId);
            return;
        }

        //assuming this is response...
        $this->collector->addResponse($context, $this->takeFromInteractionIds());
    }

    private function addToInteractionIds(string $interactionId)
    {
        $this->interactionIds[] = $interactionId;
    }

    private function takeFromInteractionIds(): string
    {
        return array_shift($this->interactionIds);
    }
}
