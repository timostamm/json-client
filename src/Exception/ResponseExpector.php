<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 31.08.18
 * Time: 17:05
 */

namespace TS\Web\JsonClient\Exception;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseExpector
{

    protected $request;
    protected $response;
    protected $handlerContext;


    public function __construct(RequestInterface $request, ResponseInterface $response, array $handlerContext = [])
    {
        $this->request = $request;
        $this->response = $response;
        $this->handlerContext = $handlerContext;
    }


    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }


    public function createException(string $message, \Exception $previous = null): UnexpectedResponseException
    {
        return new UnexpectedResponseException($message, $this->request, $this->response, $previous, $this->handlerContext);
    }


    /**
     * Ensure that the response has the expected content type.
     * If the expected type contains a charset, it must match.
     * If the expected type does not contain a charset, the
     * charset is ignored when matching the type.
     *
     * @param string $expectedType
     */
    public function expectType(string $expectedType): void
    {
        $actualType = $this->response->getHeaderLine('Content-Type');
        if ($actualType === $expectedType) {
            return;
        }
        $actual = explode('; charset=', $actualType);
        $expected = explode('; charset=', $expectedType);
        if (empty($expected[1]) && $expected[0] === $actual[0]) {
            return;
        }
        $msg = sprintf('Expected response content type to be %s, got %s instead.', $expectedType, $actualType);
        throw $this->createException($msg);
    }


    public function expectStatusCode(int $expectedCode): void
    {
        $actualCode = $this->response->getStatusCode();
        if ($actualCode === $expectedCode) {
            return;
        }
        $msg = sprintf('Expected response status code %s, got %s instead.', $expectedCode, $actualCode);
        throw $this->createException($msg);
    }


    public function requireHeaderLine(string $header): string
    {
        if (!$this->response->hasHeader($header)) {
            $msg = sprintf('Expected response to have header %s.', $header);
            throw $this->createException($msg);
        }
        return $this->response->getHeaderLine($header);
    }


}