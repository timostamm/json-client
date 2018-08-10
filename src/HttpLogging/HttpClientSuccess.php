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

class HttpClientSuccess
{

    private $request;
    private $response;
    private $requestOptions;


    public function __construct(RequestInterface $request, ResponseInterface $response, array $requestOptions)
    {
        $this->request = $request;
        $this->response = $response;
        $this->requestOptions = $requestOptions;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getRequestOptions(): array
    {
        return $this->requestOptions;
    }


}
