<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\Controller;

use Markup\ElasticsearchBundle\Controller\KibanaConsoleJsonController;
use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\HttpKernel\Profiler\Profiler;

class KibanaConsoleJsonControllerTest extends MockeryTestCase
{
    /**
     * @var string
     */
    private $token;

    /**
     * @var Profile|m\MockInterface
     */
    private $profile;

    /**
     * @var Profiler|m\MockInterface
     */
    private $profiler;

    /**
     * @var ElasticDataCollector|m\MockInterface
     */
    private $collector;

    /**
     * @var KibanaConsoleJsonController
     */
    private $controller;

    protected function setUp()
    {
        $this->token = "💵";
        $this->profile = m::mock(Profile::class);
        $this->profiler = m::mock(Profiler::class)
            ->shouldReceive('loadProfile')
            ->with($this->token)
            ->andReturn($this->profile)
            ->getMock();
        $this->collector = m::mock(ElasticDataCollector::class);
        $this->profile
            ->shouldReceive('getCollector')
            ->with(ElasticDataCollector::NAME)
            ->andReturn($this->collector);
        $this->controller = new KibanaConsoleJsonController();
    }

    public function testNoProfilerReturns404()
    {
        $interactionId = "🤷🏼‍♀️";
        $response = $this->controller->viewConsoleJson(
            $this->token,
            $interactionId,
            null
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testUnknownInteractionReturns404()
    {
        $interactionId = "☠️";
        $this->collector
            ->shouldReceive('getInteraction')
            ->with($interactionId)
            ->andReturnNull();
        $response = $this->controller->viewConsoleJson(
            $this->token,
            $interactionId,
            $this->profiler
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testKnownInteractionReturns200WithCorrectHeaders()
    {
        $interaction = [
            'requests' => null,
            'response' => null,
            'http_method' => 'GET',
            'uri' => '/_search',
            'status_code' => 200,
            'duration_in_ms' => 42,
            'request_body' => json_encode(['this'=>'that']),
        ];
        $interactionId = "💫";
        $this->collector
            ->shouldReceive('getInteraction')
            ->with($interactionId)
            ->andReturn($interaction);

        $response = $this->controller->viewConsoleJson($this->token, $interactionId, $this->profiler);

        $this->assertEquals(200, $response->getStatusCode());
        $headerBag = $response->headers;
        $this->assertEquals('application/json', $headerBag->get('Content-Type'));
        $this->assertEquals('*', $headerBag->get('Access-Control-Allow-Origin'));
    }
}
