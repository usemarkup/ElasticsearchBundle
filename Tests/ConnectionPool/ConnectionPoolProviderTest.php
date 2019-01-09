<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\ConnectionPool;

use Elasticsearch\ConnectionPool\SimpleConnectionPool;
use Elasticsearch\ConnectionPool\SniffingConnectionPool;
use Elasticsearch\ConnectionPool\StaticConnectionPool;
use Elasticsearch\ConnectionPool\StaticNoPingConnectionPool;
use Markup\ElasticsearchBundle\ConnectionPool\ConnectionPoolProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ConnectionPoolProviderTest extends TestCase
{
    private const MY_TEST_POOL = 'MyTestPool';

    /**
     * @var ConnectionPoolProvider
     */
    private $poolProvider;

    protected function setUp()
    {
        $serviceLocator = new ServiceLocator([
            'my_custom' => function () {
                return self::MY_TEST_POOL;
            },
        ]);
        $this->poolProvider = new ConnectionPoolProvider($serviceLocator);
    }

    public function testGetStaticNoPing()
    {
        $this->assertEquals(
            StaticNoPingConnectionPool::class,
            $this->poolProvider->retrieveConnectionPool('static_no_ping')
        );
    }

    public function testGetStatic()
    {
        $this->assertEquals(
            StaticConnectionPool::class,
            $this->poolProvider->retrieveConnectionPool('static')
        );
    }

    public function testGetSimple()
    {
        $this->assertEquals(
            SimpleConnectionPool::class,
            $this->poolProvider->retrieveConnectionPool('simple')
        );
    }

    public function testGetSniffing()
    {
        $this->assertEquals(
            SniffingConnectionPool::class,
            $this->poolProvider->retrieveConnectionPool('sniffing')
        );
    }

    public function testGetCustomPool()
    {
        $this->assertEquals(
            self::MY_TEST_POOL,
            $this->poolProvider->retrieveConnectionPool('my_custom')
        );
    }
}
