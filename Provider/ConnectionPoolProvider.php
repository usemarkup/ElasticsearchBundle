<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Provider;

use Elasticsearch\ConnectionPool;
use Psr\Container\ContainerInterface;

/**
 * A provider that can provide a connection pool reference (either FQCN or object) for a given pool name.
 */
class ConnectionPoolProvider
{
    const KNOWN_POOLS = [
        'static_no_ping' => ConnectionPool\StaticNoPingConnectionPool::class,
        'static' => ConnectionPool\StaticConnectionPool::class,
        'simple' => ConnectionPool\SimpleConnectionPool::class,
        'sniffing' => ConnectionPool\SniffingConnectionPool::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $customPoolLocator;

    public function __construct(ContainerInterface $customPoolLocator)
    {
        $this->customPoolLocator = $customPoolLocator;
    }

    /**
     * @param string $poolName
     * @return ConnectionPool\AbstractConnectionPool|string
     */
    public function retrieveConnectionPool(string $poolName)
    {
        if (array_key_exists($poolName, self::KNOWN_POOLS)) {
            return self::KNOWN_POOLS[$poolName];
        }
        if (!$this->customPoolLocator->has($poolName)) {
            throw new \LogicException(
                sprintf(
                    'Client expected unknown Elasticsearch connection pool "%s".',
                    $poolName
                )
            );
        }

        return $this->customPoolLocator->get($poolName);
    }
}
