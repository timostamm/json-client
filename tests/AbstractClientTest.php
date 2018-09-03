<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 15.05.18
 * Time: 14:02
 */

namespace TS\Web\JsonClient;


use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TS\Web\JsonClient\Exception\ServerMessageException;
use TS\Web\JsonClient\Exception\UnexpectedResponseException;
use TS\Web\JsonClient\Fixtures\Payload;
use TS\Web\JsonClient\Fixtures\TestClient;
use function GuzzleHttp\Psr7\stream_for;


class AbstractClientTest extends TestCase
{

    /** @var TestClient */
    protected $client;

    /** @var MockHandler */
    protected $mockHandler;

    /** @var HandlerStack */
    protected $handlerStack;

    /** @var MockObject | SerializerInterface */
    protected $serializer;


    protected function setUp()
    {
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->mockHandler = new MockHandler();
        $this->handlerStack = HandlerStack::create($this->mockHandler);
        $this->client = new TestClient([
            'handler' => $this->handlerStack,
            'base_uri' => 'http://localhost',
        ], $this->serializer);
    }


    public function testAcceptHeaderSent()
    {
        $history = [];
        $this->handlerStack->push(Middleware::history($history));
        $this->mockHandler->append(new Response(200, ['Content-Type' => 'application/json'], stream_for('{"message":"Hello"}')));

        $this->client->getBodyString();

        /** @var RequestInterface $request */
        $request = $history[0]['request'];
        $this->assertEquals('GET', $request->getMethod());
        $this->assertNotEmpty($request->getHeaderLine('Accept'));
        $this->assertEquals('application/json', $request->getHeaderLine('Accept'));
    }


    public function testDeserializeResponseExceptionThrowsUnexpectedResponseException()
    {
        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with('placeholder-payload-json', Payload::class, 'json', [])
            ->willThrowException(new \RuntimeException('serializer error'));

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], stream_for('placeholder-payload-json'))
        );

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Failed to deserialize response body to type TS\Web\JsonClient\Fixtures\Payload: serializer error');
        $this->client->getPayload();

    }


    public function testDeserializeResponse()
    {
        $payload = new Payload('str', 123);

        $this->serializer->expects($this->once())
            ->method('deserialize')
            ->with('placeholder-payload-json', Payload::class, 'json')
            ->willReturn($payload);

        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'], stream_for('placeholder-payload-json'))
        );

        $result = $this->client->getPayload();
        $this->assertSame($payload, $result);
    }


    public function testUnexpectedResponseType()
    {
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'text/html'])
        );

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Expected response content type to be application/json, got text/html instead.');
        $this->client->getJsonResponse();
    }


    public function testExpectType()
    {
        $this->mockHandler->append(
            new Response(200, ['Content-Type' => 'application/json'])
        );

        $history = [];
        $this->handlerStack->push(Middleware::history($history));

        $this->client->getJsonResponse();
        $this->assertCount(1, $history);
    }


    public function test_HttpErrors_Before_ExpectType()
    {
        $this->mockHandler->append(
            new Response(404)
        );

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Client error: `GET http://localhost/json-response` resulted in a `404 Not Found` response');
        $this->client->getJsonResponse();
    }


    public function testRequestSerialization()
    {
        $payload = new Payload('str', 123);

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with($payload, 'json', [])
            ->willReturn('placeholder-payload-json');


        $history = [];
        $this->handlerStack->push(Middleware::history($history));
        $this->mockHandler->append(
            new Response(200)
        );

        $this->client->sendPayload($payload);

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
        ], $this->serializer);

        $this->expectException(ConnectException::class);
        $client->getBodyString();
    }


    public function testHttpErrors()
    {
        $this->mockHandler->append(
            new Response(404, [], 'not found')
        );

        $this->expectException(ClientException::class);
        $this->client->getBodyString();
    }


    /**
     * @dataProvider provideServerErrorMessages
     */
    public function testServerMessageException(int $responseStatus, string $responseBody, string $erroMessage)
    {
        $this->mockHandler->append(
            new Response(
                $responseStatus,
                ['Content-Type' => 'application/json'],
                stream_for($responseBody)
            )
        );

        $this->expectException(ServerMessageException::class);
        $this->expectExceptionMessage($erroMessage);
        $this->client->getBodyString();
    }


    public function provideServerErrorMessages()
    {
        yield [403, '{"message":"An error message from the server."}', 'An error message from the server.'];
        yield [503, '{"message":"An error message from the server."}', 'An error message from the server.'];
        yield [403, '{"message":"An error message from the server."}', 'An error message from the server.'];
        yield [403, '{"message":"An error message from the server."}', 'An error message from the server.'];
    }

}