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


/**
 *
 * This middleware delegates logging to a HttpLoggerInterface
 * implementation.
 *
 * It logs the start, resolution and rejection of a request
 * and includes the request and response objects.
 *
 * See implementations of HttpLoggerInterface in the namespace
 * TS\Web\JsonClient\HttpLogging
 *
 */
class HttpLoggingMiddleware
{

    const REQUEST_OPTION_LOGGER = 'logger';


    public function __invoke($handler)
    {

        return function (RequestInterface $request, array $options) use ($handler) {

            $transferStart = -microtime(true);
            $logger = $options[self::REQUEST_OPTION_LOGGER] ?? HttpNullLogger::getInstance();
            $request = $logger->logStart($request, $options);

            /** @var PromiseInterface $promise */
            $promise = $handler($request, $options);

            return $promise->then(function (ResponseInterface $response) use ($request, $options, $logger, $transferStart) {

                $transferTimeSeconds = $transferStart + microtime(true);
                $logger->logSuccess($request, $response, $options, $transferTimeSeconds);
                return $response;

            }, function (\Throwable $reason) use ($request, $options, $logger, $transferStart) {

                $response = $reason instanceof RequestException ? $reason->getResponse() : null;
                $transferTimeSeconds = $transferStart + microtime(true);
                $logger->logFailure($request, $response, $reason, $options, $transferTimeSeconds);

                return rejection_for($reason);
            });
        };
    }


}
