<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Provider;

use Elasticsearch\Connections\ConnectionFactoryInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class ConnectionFactoryProvider
{
    /**
     * @var ContainerInterface
     */
    private $customConnectionFactoryLocator;

    public function __construct(ContainerInterface $customConnectionFactoryLocator)
    {
        $this->customConnectionFactoryLocator = $customConnectionFactoryLocator;
    }

    public function retrieveConnectionFactory(string $connectionFactoryName): ConnectionFactoryInterface
    {
        try {
            return $this->customConnectionFactoryLocator->get($connectionFactoryName);
        } catch (ContainerExceptionInterface $e) {
            throw new \LogicException(
                sprintf(
                    'Client expected unknown connection factory "%s".',
                    $connectionFactoryName
                )
            );
        }
    }
}
