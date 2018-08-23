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

    function logStart(RequestInterface $request, array $requestOptions): RequestInterface;

    function logSuccess(RequestInterface $request, ResponseInterface $response, array $requestOptions): void;

    function logFailure(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions): void;


}
