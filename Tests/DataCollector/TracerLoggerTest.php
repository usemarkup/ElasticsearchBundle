<?php

declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\DataCollector;

use Markup\ElasticsearchBundle\DataCollector\ElasticDataCollector;
use Markup\ElasticsearchBundle\DataCollector\TracerLogger;
use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Psr\Log\LoggerInterface;

class TracerLoggerTest extends MockeryTestCase
{
    /**
     * @var ElasticDataCollector|m\MockInterface
     */
    private $collector;

    /**
     * @var TracerLogger
     */
    private $logger;

    protected function setUp()
    {
        $this->collector = m::spy(ElasticDataCollector::class);
        $this->logger = new TracerLogger($this->collector);
    }

    public function testIsLogger()
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->logger);
    }

    public function testLogForCurlCommandAddsRequest()
    {
        $message = $this->getTestCurlCommand();

        $this->logger->info($message);

        $this->collector
            ->shouldHaveReceived('addRequest');
    }

    public function testLogForBulkCurlCommandAddsRequestPayloads()
    {
        $message = $this->getTestBulkCurlCommand();

        $this->logger->info($message);

        $this->collector
            ->shouldHaveReceived('addRequest');
    }

    public function testLogForResponsePayload()
    {
        $payload = [
            'this' => 'that',
            'stuff' => 'things',
        ];

        $this->logger->debug('Response:', $payload);

        $this->collector
            ->shouldHaveReceived('addResponse');
    }

    private function getTestCurlCommand(): string
    {
        return 'curl -XGET \'http://localhost:9200/catalog/_doc/_search?pretty=true\' -d \'{"query":{"constant_score":{"filter":{"bool":{"must":[{"match_all":{}},{"term":{"section":"men"}},{"term":{"category_en_GB":"shirts"}},{"term":{"on_sale_gb_customer":"false"}},{"term":{"available_to_purchase_gb":true}},{"term":{"has_images":true}}]}}}},"_source":["entity_id","id","style_code"],"aggs":{"size":{"terms":{"field":"size_eur_en_GB_web"}},"price":{"terms":{"field":"price_gb_customer"}},"color_group":{"terms":{"field":"color_group_en_GB"}},"fit":{"terms":{"field":"fit"}}},"size":60,"from":0}\'';
    }

    private function getTestBulkCurlCommand(): string
    {
        return 'curl -XGET \'http://localhost:9200/catalog/_doc/_search?pretty=true\' -d \'{"query":{"constant_score":{"filter":{"bool":{"must":[{"match_all":{}},{"term":{"section":"men"}},{"term":{"category_en_GB":"shirts"}},{"term":{"on_sale_gb_customer":"false"}},{"term":{"available_to_purchase_gb":true}},{"term":{"has_images":true}}]}}}},"_source":["entity_id","id","style_code"],"aggs":{"size":{"terms":{"field":"size_eur_en_GB_web"}},"price":{"terms":{"field":"price_gb_customer"}},"color_group":{"terms":{"field":"color_group_en_GB"}},"fit":{"terms":{"field":"fit"}}},"size":60,"from":0}
{"query":{"constant_score":{"filter":{"bool":{"must":[{"match_all":{}},{"term":{"section":"men"}},{"term":{"category_en_GB":"shirts"}},{"term":{"on_sale_gb_customer":"false"}},{"term":{"available_to_purchase_gb":true}},{"term":{"has_images":true}}]}}}},"_source":["entity_id","id","style_code"],"aggs":{"size":{"terms":{"field":"size_eur_en_GB_web"}},"price":{"terms":{"field":"price_gb_customer"}},"color_group":{"terms":{"field":"color_group_en_GB"}},"fit":{"terms":{"field":"fit"}}},"size":60,"from":0}\'';
    }
}
