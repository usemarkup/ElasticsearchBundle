<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Provider;

use Elasticsearch\Serializers;
use Psr\Container\ContainerInterface;

class SerializerProvider
{
    const KNOWN_SERIALIZERS = [
        'smart' => Serializers\SmartSerializer::class,
        'array_to_json' => Serializers\ArrayToJSONSerializer::class,
        'everything_to_json' => Serializers\EverythingToJSONSerializer::class,
    ];

    /**
     * @var ContainerInterface
     */
    private $customSerializerLocator;

    public function __construct(ContainerInterface $customSerializerLocator)
    {
        $this->customSerializerLocator = $customSerializerLocator;
    }

    /**
     * @param string $serializerName
     * @return Serializers\SerializerInterface|string
     */
    public function retrieveSerializer(string $serializerName)
    {
        if (array_key_exists($serializerName, self::KNOWN_SERIALIZERS)) {
            return self::KNOWN_SERIALIZERS[$serializerName];
        }
        if (!$this->customSerializerLocator->has($serializerName)) {
            throw new \LogicException(
                sprintf(
                    'Client expected unknown Elasticsearch serializer "%s".',
                    $serializerName
                )
            );
        }

        return $this->customSerializerLocator->get($serializerName);
    }
}
