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

    public function addRequest(array $payload)
    {
        $this->data['requests'][] = $payload;
    }

    public function addResponse(array $payload)
    {
        $this->data['responses'][] = $payload;
    }

    public function getInteractions(): array
    {
        return array_map(
            function ($request, $response) {
                return [
                    'request' => $request,
                    'response' => $response['response'],
                    'http_method' => $response['method'],
                    'uri' => $response['uri'],
                    'status_code' => $response['HTTP code'],
                    'duration_in_ms' => $response['duration']*1000,
                ];
            },
            $this->data['requests'],
            $this->data['responses']
        );
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
        ];
    }
}
