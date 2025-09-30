<?php

namespace TS\Web\JsonClient\Middleware;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\SerializerInterface;
use TS\Web\JsonClient\Exception\UnexpectedResponseException;
use TS\Web\JsonClient\Fixtures\Payload;
use TS\Web\JsonClient\HttpMessage\DeserializedResponse;

class DeserializeResponseMiddlewareTest extends TestCase
{
    private $middleware;
    private $nextHandler;
    private $serializer;

    protected function setUp(): void
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->nextHandler = function ($request, $options) {
            $promise = new Promise(function () use (&$promise) {
                $response = new Response(200, ['Content-Type' => 'application/json'], Utils::streamFor('{"id": 1, "name": "test"}'));
                $promise->resolve($response);
            });
            return $promise;
        };
        $this->middleware = new DeserializeResponseMiddleware($this->nextHandler, $this->serializer);
    }

    public function testSuccessfulDeserialization()
    {
        $payload = new Payload('test', 1);
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with('{"id": 1, "name": "test"}', Payload::class, 'json')
            ->willReturn($payload);

        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getBody')
            ->willReturn(Utils::streamFor(''));

        $options = ['deserialize_to' => Payload::class];

        $promise = $this->middleware->__invoke($request, $options);
        $response = $promise->wait();
        $this->assertInstanceOf(DeserializedResponse::class, $response);

        $this->assertSame($payload, $response->getDeserializedData());
    }

    public function testSuccessfulDeserializationToArray()
    {
        $payload = [new Payload('test', 1), new Payload('test', 2), new Payload('test', 3)];
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with('{"id": 1, "name": "test"}', Payload::class . '[]', 'json')
            ->willReturn($payload);

        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getBody')
            ->willReturn(Utils::streamFor(''));

        $options = ['deserialize_to' => Payload::class . '[]'];

        $promise = $this->middleware->__invoke($request, $options);
        $response = $promise->wait();

        $this->assertInstanceOf(DeserializedResponse::class, $response);

        $this->assertSame($payload, $response->getDeserializedData());
    }

    public function testNonJsonResponseThrowsException()
    {
        $this->nextHandler = function ($request, $options) {
            $promise = new Promise(function () use (&$promise) {
                $response = new Response(200, [], Utils::streamFor('not json'));
                $promise->resolve($response);
            });
            return $promise;
        };
        $this->middleware = new DeserializeResponseMiddleware($this->nextHandler, $this->serializer);

        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getBody')
            ->willReturn(Utils::streamFor(''));

        $options = ['deserialize_to' => Payload::class];

        $promise = $this->middleware->__invoke($request, $options);

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Expected response content type to be application/json, got  instead.');

        $promise->wait();
    }

    public function testSerializerExceptionThrowsUnexpectedResponseException()
    {
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->willThrowException(new \Exception('Serialization error'));

        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getBody')
            ->willReturn(Utils::streamFor(''));

        $options = ['deserialize_to' => Payload::class];

        $nextHandler = function ($request, $options) {
            $promise = new Promise(function () use (&$promise) {
                $response = new Response(200, ['Content-Type' => 'application/json'], Utils::streamFor('{"id": 1, "name": "test"}'));
                $promise->resolve($response);
            });
            return $promise;
        };
        $middleware = new DeserializeResponseMiddleware($nextHandler, $this->serializer);
        $promise = $middleware->__invoke($request, $options);

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Failed to deserialize response body to type TS\Web\JsonClient\Fixtures\Payload: Serialization error');

        $promise->wait();
    }

    public function testNoDeserializeToOptionReturnsOriginalResponse()
    {
        $request = $this->createMock(\Psr\Http\Message\RequestInterface::class);
        $request->method('getBody')
            ->willReturn(Utils::streamFor(''));

        $options = [];

        $promise = $this->middleware->__invoke($request, $options);
        $response = $promise->wait();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
