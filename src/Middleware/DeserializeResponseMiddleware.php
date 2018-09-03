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
use Symfony\Component\Serializer\SerializerInterface;


/**
 *
 * This middleware can decode/deserialize JSON response
 * bodies.
 *
 * It changes the contract of the Guzzle HttpClient! The
 * return values are no longer ResponseInterface
 * instances, but values deserialized from the response.
 *
 * This change is necessary to for the integration of
 * deserialization exceptions. Thrown exceptions are
 * based on BadResponseException and contain the request
 * and response object used in the handler.
 *
 *
 * The middleware provides the following request options:
 *
 * "deserialize_to"
 *
 * A string that represents a valid type for the symfony
 * serializer. This is usually a fully qualified class
 * name.
 *
 * OR: A callable that accepts a single argument of the
 * type ResponseDeserializer. The callable has full control
 * over the deserialization and has to return the
 * deserialized value.
 *
 *
 * "deserialize_context"
 *
 * An optional array, passed on as the context to the
 * symfony serializer.
 *
 */
class DeserializeResponseMiddleware
{

    const REQUEST_OPTION_DESERIALIZE_TO = 'deserialize_to';
    const REQUEST_OPTION_DESERIALIZE_CONTEXT = 'deserialize_context';
    const REQUEST_OPTION_DESERIALIZE = 'deserialize';


    private $serializer;
    private $nextHandler;


    public function __construct(callable $nextHandler, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->nextHandler = $nextHandler;
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        $handler = $this->nextHandler;

        /** @var PromiseInterface $promise */
        $promise = $handler($request, $options);

        return $promise->then(function (ResponseInterface $response) use ($request, $options) {

            $intercept = array_key_exists(self::REQUEST_OPTION_DESERIALIZE, $options)
                || array_key_exists(self::REQUEST_OPTION_DESERIALIZE, $options)
                || array_key_exists(self::REQUEST_OPTION_DESERIALIZE_TO, $options);

            if (!$intercept) {
                return $response;
            }

            $deserialize_context = $options[self::REQUEST_OPTION_DESERIALIZE_CONTEXT] ?? [];
            $deserializer = new ResponseDeserializer($this->serializer, $deserialize_context, $request, $response);
            $deserialize_to = $options[self::REQUEST_OPTION_DESERIALIZE_TO] ?? false;

            if (is_string($deserialize_to)) {

                return $deserializer->deserializeBody($deserialize_to);

            } else if (is_callable($deserialize_to)) {

                return $deserialize_to($deserializer);

            } else {

                return $response;

            }
        });

    }


}
