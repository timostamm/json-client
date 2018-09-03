<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 11.05.18
 * Time: 16:36
 */

namespace TS\Web\JsonClient\Middleware;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TS\Web\JsonClient\Exception\ServerMessageException;
use TS\Web\JsonClient\Exception\UnexpectedResponseException;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\Psr7\stream_for;


/**
 * This middleware intercepts all responses with status
 * code >= 400 with content type application/json.
 *
 * It decodes the JSON body and throws a
 * ServerMessageException with a message and optionally
 * a details string and a request id.
 *
 */
class ServerMessageMiddleware
{

    private $nextHandler;


    public function __construct(callable $nextHandler)
    {
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        $handler = $this->nextHandler;

        if (empty($options[RequestOptions::HTTP_ERRORS])) {
            return $handler($request, $options);
        }

        /** @var PromiseInterface $promise */
        $promise = $handler($request, $options);

        return $promise->then(function (ResponseInterface $response) use ($request) {

            $code = $response->getStatusCode();
            if ($code < 400) {
                return $response;
            }

            return $this->tryMessageException($response, $request);

        });

    }

    private function tryMessageException(ResponseInterface $response, RequestInterface $request): ResponseInterface
    {
        if ('application/json' !== $response->getHeaderLine('Content-type')) {
            return $response;
        }

        $body = $response->getBody()->getContents();

        // restore response body
        $response = $response->withBody(stream_for($body));

        try {

            $json = json_decode($body, true);

        } catch (\InvalidArgumentException $exception) {
            $msg = 'Failed to decode json response: ' . $exception->getMessage();
            throw new UnexpectedResponseException($msg, $response, $exception);
        }

        if (!array_key_exists('message', $json)) {
            return $response;
        }

        $message = $json['message'];
        $details = $json['details'] ?? null;
        $request_id = $json['request_id'] ?? null;
        throw new ServerMessageException($message, $details, $request_id, $request, $response);
    }


}
