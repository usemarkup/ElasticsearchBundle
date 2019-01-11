<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\Provider;

use Elasticsearch\ClientBuilder;
use GuzzleHttp\Ring\Client\CurlMultiHandler;
use Markup\ElasticsearchBundle\Provider\HandlerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class HandlerProviderTest extends TestCase
{
    private const MY_TEST_HANDLER = 'MyTestHandler';

    /**
     * @var HandlerProvider
     */
    private $handlerProvider;

    protected function setUp()
    {
        $serviceLocator = new ServiceLocator([
            'my_custom' => $this->getClosureForName(self::MY_TEST_HANDLER),
            'default' => $this->getClosureForName('default'),
            'single' => $this->getClosureForName('single'),
            'multi' => $this->getClosureForName('multi'),
        ]);
        $this->handlerProvider = new HandlerProvider($serviceLocator);
    }

    public function testGetDefaultHandler()
    {
        $this->assertEquals(
            $this->getClosureForName('default'),
            $this->handlerProvider->retrieveHandler('default')
        );
    }

    public function testGetSingleHandler()
    {
        $this->assertEquals(
            $this->getClosureForName('single'),
            $this->handlerProvider->retrieveHandler('single')
        );
    }

    public function testGetMultiHandler()
    {
        $this->assertEquals(
            $this->getClosureForName('multi'),
            $this->handlerProvider->retrieveHandler('multi')
        );
    }

    public function testGetCustomHandler()
    {
        $this->assertEquals(
            function () {
                return self::MY_TEST_HANDLER;
            },
            $this->handlerProvider->retrieveHandler('my_custom')
        );
    }

    private function getClosureForName(string $name): callable
    {
        return function () use ($name) {
            return function () use ($name) {
                return $name;
            };
        };
    }
}
