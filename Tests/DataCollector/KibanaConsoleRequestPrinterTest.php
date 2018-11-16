<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\DataCollector;

use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Markup\ElasticsearchBundle\DataCollector\KibanaConsoleRequestPrinter;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class KibanaConsoleRequestPrinterTest extends MockeryTestCase
{
    /**
     * @var ElasticDataCollector|m\MockInterface
     */
    private $collector;

    /**
     * @var KibanaConsoleRequestPrinter
     */
    private $printer;

    protected function setUp()
    {
        $this->collector = m::mock(ElasticDataCollector::class);
        $this->printer = new KibanaConsoleRequestPrinter();
    }

    public function testReturnsNullIfCollectorHasNothingForInteraction()
    {
        $interactionId = 'unknown';
        $this->collector
            ->shouldReceive('getInteraction')
            ->with($interactionId)
            ->andReturnNull();

        $this->assertNull($this->printer->printConsoleJsonBody($this->collector, $interactionId));
    }

    public function testReturnsConsoleJsonIfCollectorHasInteraction()
    {
        $interactionId = '12345';
        $json = '{"this": "that"}';
        $method = 'GET';
        $uri = '/index/_search';
        $this->collector
            ->shouldReceive('getInteraction')
            ->with($interactionId)
            ->andReturn([
               'http_method' => $method,
               'uri' => $uri,
               'request_body' => $json,
            ]);
        $expected = file_get_contents(__DIR__.'/fixtures/testConsoleBody.json');

        $this->assertEquals($expected, $this->printer->printConsoleJsonBody($this->collector, $interactionId));
    }
}
