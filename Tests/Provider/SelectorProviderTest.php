<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\Provider;

use Elasticsearch\ConnectionPool\Selectors\RandomSelector;
use Elasticsearch\ConnectionPool\Selectors\RoundRobinSelector;
use Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector;
use Markup\ElasticsearchBundle\Provider\SelectorProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SelectorProviderTest extends TestCase
{
    private const MY_TEST_SELECTOR = 'MyTestSelector';

    /**
     * @var SelectorProvider
     */
    private $selectorProvider;

    protected function setUp()
    {
        $serviceLocator = new ServiceLocator([
            'my_custom' => function () {
                return self::MY_TEST_SELECTOR;
            },
        ]);
        $this->selectorProvider = new SelectorProvider($serviceLocator);
    }

    public function testGetRoundRobinSelector()
    {
        $this->assertEquals(
            RoundRobinSelector::class,
            $this->selectorProvider->retrieveSelector('round_robin')
        );
    }

    public function testGetStickyRoundRobin()
    {
        $this->assertEquals(
            StickyRoundRobinSelector::class,
            $this->selectorProvider->retrieveSelector('sticky_round_robin')
        );
    }

    public function testGetRandom()
    {
        $this->assertEquals(
            RandomSelector::class,
            $this->selectorProvider->retrieveSelector('random')
        );
    }

    public function testGetCustomSelector()
    {
        $this->assertEquals(
            self::MY_TEST_SELECTOR,
            $this->selectorProvider->retrieveSelector('my_custom')
        );
    }
}
