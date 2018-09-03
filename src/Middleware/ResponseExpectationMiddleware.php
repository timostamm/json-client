<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 11.05.18
 * Time: 15:11
 */

namespace TS\Web\JsonClient\Middleware;


use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TS\Web\JsonClient\Exception\ResponseExpector;


/**
 *
 * This middleware can be used to make sure that
 * the response meets certain expectations.
 *
 * The main reason for this middleware is to be
 * able to create exceptions that are based on
 * BadResponseException and contain the request
 * and response object used in the handler.
 *
 *
 * This middleware provides the following request
 * options:
 *
 * "expect_response"
 *
 * A callable that will be called with a single
 * argument, a ResponseExpector instance.
 *
 * Example:
 *
 * $client->get('foo', [
 *   'expect_response' =>
 *     function(ResponseExpector $expect){
 *       throw $expect->createExpection('message');
 *     }
 * ])
 *
 *
 * "expect_response_type"
 *
 * This is a shortcut for a "expect_response" that
 * checks the response content type.
 *
 *
 * "expect_response_code"
 *
 * This is a shortcut for a "expect_response" that
 * checks the response status code.
 *
 *
 */
class ResponseExpectationMiddleware
{

    const REQUEST_OPTION_EXPECT_RESPONSE = 'expect_response';
    const REQUEST_OPTION_EXPECT_RESPONSE_TYPE = 'expect_response_type';
    const REQUEST_OPTION_EXPECT_RESPONSE_CODE = 'expect_response_code';

    private $nextHandler;


    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }


    public function __invoke(RequestInterface $request, array $options)
    {
        $handler = $this->nextHandler;

        /** @var PromiseInterface $promise */
        $promise = $handler($request, $options);

        $intercept = array_key_exists(self::REQUEST_OPTION_EXPECT_RESPONSE, $options)
            || array_key_exists(self::REQUEST_OPTION_EXPECT_RESPONSE_TYPE, $options)
            || array_key_exists(self::REQUEST_OPTION_EXPECT_RESPONSE_CODE, $options);

        if (!$intercept) {
            return $promise;
        }

        return $promise->then(function (ResponseInterface $response) use ($request, $options): ResponseInterface {

            $expector = new ResponseExpector($request, $response);

            $expect = $options[self::REQUEST_OPTION_EXPECT_RESPONSE] ?? null;
            $expectType = $options[self::REQUEST_OPTION_EXPECT_RESPONSE_TYPE] ?? null;
            $expectCode = $options[self::REQUEST_OPTION_EXPECT_RESPONSE_CODE] ?? null;

            if (is_callable($expect)) {
                $expect($expector);
            }

            if (is_string($expectType)) {
                $expector->expectType($expectType);
            }

            if (is_int($expectCode)) {
                $expector->expectStatusCode($expectCode);
            }

            return $response;
        });

    }


}
