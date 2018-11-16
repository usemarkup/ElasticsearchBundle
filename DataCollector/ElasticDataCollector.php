<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * Symfony profiling collector for showing traces of Elasticsearch interactions.
 */
class ElasticDataCollector extends DataCollector
{
    const NAME = 'markup_elasticsearch';

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Collects data for the given Request and Response.
     */
    public function collect(Request $request, Response $response, ?\Exception $exception = null)
    {
        // data is collected separately
    }

    public function addRequest(array $payload, string $interactionId)
    {
        $this->data['requests'][$interactionId] = $payload;
    }

    public function addRequestBody(string $body, string $interactionId)
    {
        $this->data['request_bodies'][$interactionId] = $body;
    }

    public function addResponse(array $payload, string $interactionId)
    {
        $this->data['responses'][$interactionId] = $payload;
    }

    public function getInteractions(): array
    {
        $interactions = [];
        foreach (array_keys($this->data['requests']) as $interactionId) {
            $interactions[$interactionId] = $this->getInteraction((string) $interactionId);
        }

        return $interactions;
    }

    public function getInteraction(string $interactionId): ?array
    {
        $request = $this->data['requests'][$interactionId] ?? null;
        if (null === $request) {
            return null;
        }
        $response = $this->data['responses'][$interactionId] ?? null;
        $requestBody = $this->data['request_bodies'][$interactionId] ?? null;

        return [
            'requests' => $request,
            'response' => $response['response'],
            'http_method' => $response['method'],
            'uri' => $response['uri'],
            'status_code' => $response['HTTP code'],
            'duration_in_ms' => $response['duration']*1000,
            'request_body' => $requestBody,
        ];
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return self::NAME;
    }

    public function reset()
    {
        $this->data = [
            'requests' => [],
            'responses' => [],
            'request_bodies' => [],
        ];
    }
}
