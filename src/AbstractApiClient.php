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


    public function __construct(string $baseUri, SerializerInterface $serializer, callable $middleware = null, array $config = [])
    {
        $this->serializer = $serializer;

        $config = array_replace([
            'base_uri' => $baseUri,
            RequestOptions::HEADERS => [
                'Accept-Encoding' => 'gzip',
                'Accept' => 'application/json'
            ]
        ], $config);

        if (empty($config['handler'])) {
            $config['handler'] = HandlerStack::create();
        }

        if (! $config['handler'] instanceof HandlerStack) {
            throw new \InvalidArgumentException('handler must be a HandlerStack');
        }

        $this->defaultMiddleware($config['handler']);

        if ($middleware) {
            $middleware( $config['handler'] );
        }

        $this->http = $this->createClient($config);
    }


    protected function defaultMiddleware(HandlerStack $stack):void
    {
        $stack->push(function (callable $handler) {
            return new ServerMessageMiddleware($handler);
        }, 'error_message');

        $stack->push(function (callable $handler) {
            return new SerializeRequestBodyMiddleware($handler, $this->serializer);
        }, 'serialize_request_body');
    }


    protected function createClient(array $config): Client
    {
        return new Client($config);
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
