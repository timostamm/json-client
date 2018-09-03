<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 31.08.18
 * Time: 17:05
 */

namespace TS\Web\JsonClient\Middleware;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\SerializerInterface;
use TS\Web\JsonClient\Exception\ResponseExpector;
use TS\Web\JsonClient\Exception\UnexpectedResponseException;

class ResponseDeserializer
{

    protected $expector;
    protected $serializer;
    protected $serializer_context;
    protected $request;
    protected $response;
    protected $handlerContext;


    public function __construct(SerializerInterface $serializer, array $serializer_context, RequestInterface $request, ResponseInterface $response, array $handlerContext = [])
    {
        $this->expector = new ResponseExpector($request, $response, $handlerContext);
        $this->serializer = $serializer;
        $this->serializer_context = $serializer_context;
        $this->request = $request;
        $this->response = $response;
        $this->handlerContext = $handlerContext;
    }


    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

    public function getExpector(): ResponseExpector
    {
        return $this->expector;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }


    public function deserializeBody(string $type, array $context = null)
    {
        $this->getExpector()->expectType('application/json');
        $context = array_replace($this->serializer_context, $context ?? []);
        $json = $this->response->getBody()->getContents();
        $this->request->getBody()->rewind();
        try {
            return $this->serializer->deserialize($json, $type, 'json', $context);
        } catch (\Exception $exception) {
            $msg = sprintf('Failed to deserialize response body to type %s: %s', $type, $exception->getMessage());
            throw $this->createException($msg, $exception);
        }
    }


    public function createException(string $message, \Exception $previous = null): UnexpectedResponseException
    {
        return $this->getExpector()->createException($message, $previous);
    }


}