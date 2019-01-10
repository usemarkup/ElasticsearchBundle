<?php
declare(strict_types=1);

namespace Markup\ElasticsearchBundle\Tests\Provider;

use Elasticsearch\Serializers\ArrayToJSONSerializer;
use Elasticsearch\Serializers\EverythingToJSONSerializer;
use Elasticsearch\Serializers\SmartSerializer;
use Markup\ElasticsearchBundle\Provider\SerializerProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

class SerializerProviderTest extends TestCase
{
    private const MY_TEST_SERIALIZER = 'MyTestSerializer';

    /**
     * @var SerializerProvider
     */
    private $serializerProvider;

    protected function setUp()
    {
        $serviceLocator = new ServiceLocator([
            'my_custom' => function () {
                return self::MY_TEST_SERIALIZER;
            },
        ]);
        $this->serializerProvider = new SerializerProvider($serviceLocator);
    }

    public function testGetSmart()
    {
        $this->assertEquals(
            SmartSerializer::class,
            $this->serializerProvider->retrieveSerializer('smart')
        );
    }

    public function testGetArrayToJSON()
    {
        $this->assertEquals(
            ArrayToJSONSerializer::class,
            $this->serializerProvider->retrieveSerializer('array_to_json')
        );
    }

    public function testGetEverythingToJSON()
    {
        $this->assertEquals(
            EverythingToJSONSerializer::class,
            $this->serializerProvider->retrieveSerializer('everything_to_json')
        );
    }

    public function testGetCustomSerializer()
    {
        $this->assertEquals(
            self::MY_TEST_SERIALIZER,
            $this->serializerProvider->retrieveSerializer('my_custom')
        );
    }
}
