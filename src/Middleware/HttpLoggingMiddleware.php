<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 10.08.18
 * Time: 12:11
 */

namespace TS\Web\JsonClient\Middleware;


use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TS\Web\JsonClient\HttpLogging\HttpNullLogger;
use function GuzzleHttp\Promise\rejection_for;


class HttpLoggingMiddleware
{

    const REQUEST_OPTION_LOGGER = 'logger';


    public function __invoke($handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {

            $logger = $options[self::REQUEST_OPTION_LOGGER] ?? HttpNullLogger::getInstance();

            $logger->logStart($request, $options);

            /** @var PromiseInterface $promise */
            $promise = $handler($request, $options);

            return $promise->then(function (ResponseInterface $response) use ($request, $options, $logger) {

                $logger->logSuccess($request, $response, $options);

                return $response;

            }, function (\Throwable $reason) use ($request, $options, $logger) {

                $response = $reason instanceof RequestException ? $reason->getResponse() : null;

                $logger->logFailure($request, $response, $reason, $options);

                return rejection_for($reason);
            });
        };
    }


}
