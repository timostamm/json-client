<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 15.05.18
 * Time: 14:02
 */

namespace TS\Web\JsonClient;


use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Serializer\SerializerInterface;


class ClientOptionsTest extends TestCase
{


    /** @var MockObject | SerializerInterface */
    protected $serializer;


    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
    }


    public function testNoOptionRequired()
    {
        $options = [];
        $this->getMockForAbstractClass(AbstractApiClient::class, [
            $options, $this->serializer
        ]);
        $this->assertTrue(true);
    }


    /**
     * @dataProvider provideDefinedOptions
     */
    public function testDefinedOptions(string $optionName, $value)
    {
        $options = [
            $optionName => $value
        ];
        $this->getMockForAbstractClass(AbstractApiClient::class, [
            $options, $this->serializer
        ]);
        $this->assertTrue(true);
    }

    public function provideDefinedOptions()
    {
        $class = new \ReflectionClass(RequestOptions::class);
        foreach ($class->getConstants() as $constant) {
            $value = 'test';
            if ($constant === RequestOptions::HEADERS) {
                $value = [];
            }
            yield [$constant, $value];
        }
    }


    /**
     * @dataProvider provideInvalidOptions
     */
    public function testAllowedOptionTypes(array $options)
    {
        $this->expectException(InvalidOptionsException::class);
        $this->getMockForAbstractClass(AbstractApiClient::class, [
            $options, $this->serializer
        ]);
    }

    public function provideInvalidOptions()
    {
        yield[[
            'base_uri' => 123
        ]];
        yield[[
            'handler' => 123
        ]];
        yield[[
            RequestOptions::HEADERS => 123
        ]];
    }

}
