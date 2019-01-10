<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Provider;

use Elasticsearch\ConnectionPool\Selectors;
use Psr\Container\ContainerInterface;

class SelectorProvider
{
    const KNOWN_SELECTORS = [
        'round_robin' => Selectors\RoundRobinSelector::class,
        'sticky_round_robin' => Selectors\StickyRoundRobinSelector::class,
        'random' => Selectors\RandomSelector::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $customSelectorLocator;

    public function __construct(ContainerInterface $customSelectorLocator)
    {
        $this->customSelectorLocator = $customSelectorLocator;
    }

    /**
     * @param string $selectorName
     * @return Selectors\SelectorInterface|string
     */
    public function retrieveSelector(string $selectorName)
    {
        if (array_key_exists($selectorName, self::KNOWN_SELECTORS)) {
            return self::KNOWN_SELECTORS[$selectorName];
        }
        if (!$this->customSelectorLocator->has($selectorName)) {
            throw new \LogicException(
                sprintf(
                    'Client expected unknown Elasticsearch connection selector "%s".',
                    $selectorName
                )
            );
        }

        return $this->customSelectorLocator->get($selectorName);
    }
}
