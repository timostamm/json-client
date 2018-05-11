<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 11.05.18
 * Time: 16:35
 */

namespace TS\Web\JsonClient;


use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TS\Web\JsonClient\Exception\UnexpectedResponseException;
use TS\Web\JsonClient\Middleware\SerializeRequestBodyMiddleware;
use TS\Web\JsonClient\Middleware\ServerMessageMiddleware;


abstract class AbstractApiClient
{

    private $serializer;
    protected $http;
    protected $defaults;


    public function __construct(string $baseUri, SerializerInterface $serializer, callable $handler = null)
    {
        $this->serializer = $serializer;
        $handlerStack = $this->createHandlerStack($handler);
        $config = $this->createConfig($baseUri, $handlerStack);
        $this->configure($config, $handlerStack);
        $this->http = $this->createClient($config, $handlerStack);
    }


    protected function createConfig(string $baseUri, HandlerStack $stack): array
    {
        return [
            'handler' => $stack,
            'base_uri' => $baseUri,
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::TIMEOUT => 2.0,
            RequestOptions::VERIFY => true,
            RequestOptions::COOKIES => false,
            RequestOptions::HEADERS => [
                'Accept-Encoding' => 'gzip',
                'Accept' => 'application/json'
            ]
        ];
    }


    /**
     * Configure the client, set config options and middleware.
     *
     * @param array $config
     * @param HandlerStack $stack
     * @return array
     */
    protected function configure(array & $config, HandlerStack $stack): void
    {
    }


    protected function createClient(array $config): Client
    {
        return new Client($config);
    }


    protected function createHandlerStack(callable $handler = null): HandlerStack
    {
        $stack = HandlerStack::create($handler);

        $stack->push(function (callable $handler) {
            return new ServerMessageMiddleware($handler);
        }, 'error_message');

        $stack->push(function (callable $handler) {
            return new SerializeRequestBodyMiddleware($handler, $this->serializer);
        }, 'serialize_request_body');

        return $stack;
    }


    /**
     * Ensure that the response has the expected content type.
     * If the expected type contains a charset, it must match.
     * If the expected type does not contain a charset, the
     * charset is ignored when matching the type.
     *
     * @param string $expectedType
     * @param ResponseInterface $response
     * @throw UnexpectedResponseException if the response content type does not match
     */
    protected function expectResponseType(string $expectedType, ResponseInterface $response): void
    {
        $actualType = $response->getHeaderLine('Content-Type');
        if ($actualType === $expectedType) {
            return;
        }
        $actual = explode('; charset=', $actualType);
        $expected = explode('; charset=', $expectedType);
        if (empty($expected[1]) && $expected[0] === $actual[0]) {
            return;
        }
        $msg = sprintf('Expected response content type to be %s, got %s instead.', $expectedType, $actualType);
        throw new UnexpectedResponseException($msg, $response);
    }


    /**
     * Deserialize the request body into the given type.
     *
     * @param ResponseInterface $response
     * @param string $type
     * @param array $context
     * @return mixed
     * @throws UnexpectedResponseException if the response is not application/json or deserialization failed
     */
    protected function deserializeResponse(ResponseInterface $response, string $type, array $context = [])
    {
        $this->expectResponseType('application/json', $response);
        $data = $response->getBody()->getContents();
        try {

            $object = $this->serializer
                ->deserialize($data, $type, 'json', $context);

            return $object;

        } catch (\Exception $exception) {
            $msg = sprintf('Failed to deserialize response body to type %s: %s', $type, $exception->getMessage());
            throw new UnexpectedResponseException($msg, $response, $exception);
        }
    }


}
