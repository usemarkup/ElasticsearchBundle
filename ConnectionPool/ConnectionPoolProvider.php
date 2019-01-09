<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\ConnectionPool;

use Elasticsearch\ConnectionPool\AbstractConnectionPool;
use Elasticsearch\ConnectionPool\SimpleConnectionPool;
use Elasticsearch\ConnectionPool\SniffingConnectionPool;
use Elasticsearch\ConnectionPool\StaticConnectionPool;
use Elasticsearch\ConnectionPool\StaticNoPingConnectionPool;
use Psr\Container\ContainerInterface;

/**
 * A provider that can provide a connection pool reference (either FQCN or object) for a given pool name.
 */
class ConnectionPoolProvider
{
    const KNOWN_POOLS = [
        'static_no_ping' => StaticNoPingConnectionPool::class,
        'static' => StaticConnectionPool::class,
        'simple' => SimpleConnectionPool::class,
        'sniffing' => SniffingConnectionPool::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $customServiceLocator;

    public function __construct(ContainerInterface $customServiceLocator)
    {
        $this->customServiceLocator = $customServiceLocator;
    }

    /**
     * @param string $poolName
     * @return AbstractConnectionPool|string
     */
    public function retrieveConnectionPool(string $poolName)
    {
        if (array_key_exists($poolName, self::KNOWN_POOLS)) {
            return self::KNOWN_POOLS[$poolName];
        }
        if (!$this->customServiceLocator->has($poolName)) {
            throw new \LogicException(sprintf('Client expected unknown connection pool "%s".', $poolName));
        }

        return $this->customServiceLocator->get($poolName);
    }
}
