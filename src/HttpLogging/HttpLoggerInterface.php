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

interface HttpLoggerInterface
{

    /**
     * @deprecated Modifying the response can lead to unexpected result because of required middleware order for logging. Method will be removed in next release.
     *
     * @param RequestInterface $request
     * @param array $requestOptions
     * @return RequestInterface
     */
    function logStart(RequestInterface $request, array $requestOptions): RequestInterface;

    function logSuccess(RequestInterface $request, ResponseInterface $response, array $requestOptions, float $transferTimeSeconds): void;

    function logFailure(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions, float $transferTimeSeconds): void;


}
