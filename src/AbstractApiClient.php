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

    private $handlerStack;
    private $serializer;
    protected $http;


    public function __construct(  SerializerInterface $serializer )
    {
        $this->serializer = $serializer;
        $this->handlerStack = $this->createHandlerStack();
        $this->http = $this->createClient($this->handlerStack);
    }


    protected function createClient(HandlerStack $handlerStack):Client
    {
        return new Client([
            'handler' => $handlerStack,
            'base_uri' => $this->getBaseUri(),
            RequestOptions::ALLOW_REDIRECTS => false,
            RequestOptions::TIMEOUT => $this->getDefaultTimeout(),
            RequestOptions::VERIFY => true,
            RequestOptions::COOKIES => false,
            RequestOptions::HEADERS => [
                'User-Agent' => $this->getUserAgent(),
                'Accept-Encoding' => 'gzip',
                'Accept' => 'application/json'
            ]
        ]);
    }


    protected function createHandlerStack():HandlerStack
    {
        $stack = HandlerStack::create();

        $stack->push(function(callable $handler) {
            return new ServerMessageMiddleware($handler);
        }, 'error_message');

        $stack->push(function(callable $handler) {
            return new SerializeRequestBodyMiddleware($handler, $this->serializer);
        }, 'serialize_request_body');

        return $stack;
    }


    abstract protected function getBaseUri():string;


    protected function getDefaultTimeout():float
    {
        return 2.0;
    }


    protected function getUserAgent():string
    {
        return 'AbstractApiClient';
    }


    protected function expectResponseType(string $type, ResponseInterface $response):void
    {
        $actual = $response->getHeaderLine('Content-Type');
        if ($actual !== $type) {
            $msg = sprintf('Expected response content type to be %s, got %s instead.', $type, $actual);
            throw new UnexpectedResponseException($msg, $response);
        }
    }


    /**
     * @param ResponseInterface $response
     * @param string $type
     * @param array $context
     * @return mixed
     */
    protected function deserializeResponse(ResponseInterface $response, string $type, array $context=[])
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
