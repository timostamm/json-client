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

class HttpMemoryLogger implements HttpLoggerInterface
{

    private $requests = [];


    /**
     * @return HttpClientSuccess[] | HttpClientFailure[]
     */
    public function getRequests(): array
    {
        return $this->requests;
    }


    public function logStart(RequestInterface $request, array $requestOptions): RequestInterface
    {
        return $request;
    }


    public function logSuccess(RequestInterface $request, ResponseInterface $response, array $requestOptions): void
    {
        $this->requests[] = new HttpClientSuccess($request, $response, $requestOptions);
    }


    public function logFailure(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions): void
    {
        $this->requests[] = new HttpClientFailure($request, $response, $reason, $requestOptions);
    }


}
