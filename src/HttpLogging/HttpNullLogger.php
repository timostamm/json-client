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

class HttpNullLogger implements HttpLoggerInterface
{


    private static $instance;

    public static function getInstance(): HttpNullLogger
    {
        if (!self::$instance) {
            self::$instance = new HttpNullLogger();
        }
        return self::$instance;
    }


    public function logStart(RequestInterface $request, array $requestOptions): RequestInterface
    {
        return $request;
    }


    public function logSuccess(RequestInterface $request, ResponseInterface $response, array $requestOptions, float $transferTimeSeconds): void
    {
    }


    public function logFailure(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions, float $transferTimeSeconds): void
    {
    }


}
