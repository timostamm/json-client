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

class HttpLoggerChain implements HttpLoggerInterface
{

    /**
     * @var HttpLoggerInterface[]
     */
    private $loggers = [];


    public function __construct()
    {
    }

    public function addLogger(HttpLoggerInterface $logger): void
    {
        $this->loggers[] = $logger;
    }

    public function logStart(RequestInterface $request, array $requestOptions): RequestInterface
    {
        foreach ($this->loggers as $logger) {
            $request = $logger->logStart($request, $requestOptions);
        }
        return $request;
    }


    public function logSuccess(RequestInterface $request, ResponseInterface $response, array $requestOptions): void
    {
        foreach ($this->loggers as $logger) {
            $logger->logSuccess($request, $response, $requestOptions);
        }
    }


    public function logFailure(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions): void
    {
        foreach ($this->loggers as $logger) {
            $logger->logFailure($request, $response, $reason, $requestOptions);
        }
    }


}
