<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 15.05.18
 * Time: 14:02
 */

namespace TS\Web\JsonClient;


use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Serializer\SerializerInterface;
use TS\Web\JsonClient\Exception\ServerMessageException;
use TS\Web\JsonClient\Exception\UnexpectedResponseException;
use TS\Web\JsonClient\Fixtures\Payload;
use TS\Web\JsonClient\Fixtures\TestClient;
use function GuzzleHttp\Psr7\stream_for;


class AbstractClientTest extends TestCase
{




    public function testAcceptHeaderSent()
    {
        $history = [];
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], stream_for('{"message":"Hello"}')),
        ]);
        $stack = HandlerStack::create($mock);
        $client = new TestClient([
            'handler' => $stack,
            'base_uri' => 'http://localhost',
        ], $this->createMock(SerializerInterface::class));
        $stack->push(Middleware::history($history));

        $client->getBodyString();

        /** @var RequestInterface $request */
        $request = $history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertNotEmpty($request->getHeaderLine('Accept'));
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
    }




    public function testDeserializeResponseExceptionThrowsUnexpectedResponseException()
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->with('placeholder-payload-json', Payload::class, 'json', [
                'allow_extra_attributes' => false
            ])
            ->willThrowException(new \RuntimeException('serializer error'));


        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], stream_for('placeholder-payload-json')),
        ]));
        $client = new TestClient([
            'handler' => $stack,
            'base_uri' => 'http://localhost',
        ], $serializer);


        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Failed to deserialize response body to type TS\Web\JsonClient\Fixtures\Payload: serializer error');
        $client->getPayload();

    }


    public function testDeserializeResponse()
    {
        $payload = new Payload('str', 123);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('deserialize')
            ->with('placeholder-payload-json', Payload::class, 'json', [
                'allow_extra_attributes' => false
            ])
            ->willReturn($payload);


        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], stream_for('placeholder-payload-json')),
        ]));
        $client = new TestClient([
            'handler' => $stack,
            'base_uri' => 'http://localhost',
        ], $serializer);


        $result = $client->getPayload();
        $this->assertSame($payload, $result);

    }


    public function testUnexpectedResponseType()
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'text/html']),
        ]));
        $client = new TestClient([
            'handler' => $stack,
            'base_uri' => 'http://localhost',
        ], $this->createMock(SerializerInterface::class));

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Expected response content type to be application/json, got text/html instead.');
        $client->getJsonResponse();
    }


    public function testExpectedResponseType()
    {
        $stack = HandlerStack::create(new MockHandler([
            new Response(200, ['Content-Type' => 'application/json']),
        ]));
        $client = new TestClient([
            'handler' => $stack,
            'base_uri' => 'http://localhost',
        ], $this->createMock(SerializerInterface::class));
        $history = [];
        $stack->push(Middleware::history($history));

        $client->getJsonResponse();
        $this->assertCount(1, $history);
    }


    public function testRequestSerialization()
    {
        $payload = new Payload('str', 123);

        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serialize')
            ->with($payload, 'json', [])
            ->willReturn('placeholder-payload-json');


        $stack = HandlerStack::create(new MockHandler([
            new Response(200),
        ]));
        $client = new TestClient([
            'handler' => $stack,
            'base_uri' => 'http://localhost',
        ], $serializer);
        $history = [];
        $stack->push(Middleware::history($history));


        $client->sendPayload($payload);


        /** @var RequestInterface $request */
        $request = $history[0]['request'];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertEquals('placeholder-payload-json', $request->getBody()->getContents());
    }




    public function testConnectException()
    {
        $client = new TestClient([
            'base_uri' => 'http://this-domain-should-not-be-registered-98452237681120.com'
        ], $this->createMock(SerializerInterface::class));

        $this->expectException(ConnectException::class);
        $client->getBodyString();
    }


    /**
     * @dataProvider provideServerErrorMessages
     */
    public function testServerMessageException(int $responseStatus, string $responseBody, string $erroMessage)
    {
        $mock = new MockHandler([
            new Response(
                $responseStatus,
                ['Content-Type' => 'application/json'],
                stream_for($responseBody)
            ),
        ]);
        $client = new TestClient([
            'handler' => HandlerStack::create($mock),
            'base_uri' => 'http://localhost'
        ], $this->createMock(SerializerInterface::class));

        $this->expectException(ServerMessageException::class);
        $this->expectExceptionMessage($erroMessage);
        $client->getBodyString();
    }

    public function provideServerErrorMessages()
    {
        yield [403, '{"message":"An error message from the server."}', 'An error message from the server.'];
        yield [503, '{"message":"An error message from the server."}', 'An error message from the server.'];
        yield [403, '{"message":"An error message from the server."}', 'An error message from the server.'];
        yield [403, '{"message":"An error message from the server."}', 'An error message from the server.'];
    }



    public function testNoOptionRequired()
    {
        $options = [];
        $this->getMockForAbstractClass(AbstractApiClient::class, [
            $options, $this->createMock(SerializerInterface::class)
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
            $options, $this->createMock(SerializerInterface::class)
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
            yield [ $constant, $value ];
        }
    }


    /**
     * @dataProvider provideInvalidOptions
     */
    public function testAllowedOptionTypes(array $options)
    {
        $this->expectException(InvalidOptionsException::class);
        $this->getMockForAbstractClass(AbstractApiClient::class, [
            $options, $this->createMock(SerializerInterface::class)
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