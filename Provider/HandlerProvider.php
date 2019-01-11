<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Provider;

use Psr\Container\ContainerInterface;

class HandlerProvider
{
    /**
     * @var ContainerInterface
     */
    private $customHandlerLocator;

    public function __construct(ContainerInterface $customHandlerLocator)
    {
        $this->customHandlerLocator = $customHandlerLocator;
    }

    public function retrieveHandler(string $handlerName): callable
    {
        if ($this->customHandlerLocator->has($handlerName)) {
            return $this->customHandlerLocator->get($handlerName);
        }
        throw new \LogicException(
            sprintf(
                'Client expected unknown RingPHP handler "%s".',
                $handlerName
            )
        );
    }
}
