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

class HttpLatestLogger implements HttpLoggerInterface
{

    /**
     * @var HttpClientSuccess | HttpClientFailure | null
     */
    private $latest;


    /**
     * @return HttpClientSuccess | HttpClientFailure | null
     */
    public function getLatest()
    {
        return $this->latest;
    }


    public function getLatestRequest(): ?RequestInterface
    {
        return $this->latest ? $this->latest->getRequest() : null;
    }


    public function getLatestResponse(): ?ResponseInterface
    {
        return $this->latest ? $this->latest->getResponse() : null;
    }


    public function logStart(RequestInterface $request, array $requestOptions): RequestInterface
    {
        $this->latest = null;
        return $request;
    }


    public function logSuccess(RequestInterface $request, ResponseInterface $response, array $requestOptions): void
    {
        $this->latest = new HttpClientSuccess($request, $response, $requestOptions);
    }


    public function logFailure(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions): void
    {
        $this->latest = new HttpClientFailure($request, $response, $reason, $requestOptions);
    }


}
