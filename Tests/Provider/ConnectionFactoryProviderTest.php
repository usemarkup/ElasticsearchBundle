<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\Provider;

use Elasticsearch\Connections\ConnectionFactoryInterface;
use Markup\ElasticsearchBundle\Provider\ConnectionFactoryProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class ConnectionFactoryProviderTest extends TestCase
{
    /**
     * @var ConnectionFactoryInterface
     */
    private $customConnectionFactory;

    /**
     * @var ConnectionFactoryProvider
     */
    private $factoryProvider;

    protected function setUp()
    {
        $this->customConnectionFactory = $this->createMock(ConnectionFactoryInterface::class);
        $serviceLocator = new ServiceLocator([
            'my_factory' => function () {
                return $this->customConnectionFactory;
            },
        ]);
        $this->factoryProvider = new ConnectionFactoryProvider($serviceLocator);
    }

    public function testGetCustomFactory()
    {
        $this->assertSame(
            $this->customConnectionFactory,
            $this->factoryProvider->retrieveConnectionFactory('my_factory')
        );
    }
}
