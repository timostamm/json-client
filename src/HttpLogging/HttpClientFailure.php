<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 10.08.18
 * Time: 12:37
 */

namespace TS\Web\JsonClient\HttpLogging;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientFailure
{

    private $request;
    private $response;
    private $reason;
    private $requestOptions;


    public function __construct(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions)
    {
        $this->request = $request;
        $this->response = $response;
        $this->reason = $reason;
        $this->requestOptions = $requestOptions;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getReason(): \Throwable
    {
        return $this->reason;
    }

    public function getRequestOptions(): array
    {
        return $this->requestOptions;
    }

}
