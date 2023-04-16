<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 11.05.18
 * Time: 16:35
 */

namespace TS\Web\JsonClient;


use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\SerializerInterface;
use TS\Web\JsonClient\Middleware\DeserializeResponseMiddleware;
use TS\Web\JsonClient\Middleware\ResponseExpectationMiddleware;
use TS\Web\JsonClient\Middleware\SerializeRequestBodyMiddleware;
use TS\Web\JsonClient\Middleware\ServerMessageMiddleware;


/**
 *
 * Transfer errors:
 *
 * Methods of this client may throw Guzzle exceptions,
 * see http://docs.guzzlephp.org/en/stable/quickstart.html#exceptions
 *
 * If a networking error occurred or an HTTP response with
 * status >= 400 is received, a GuzzleHttp\Exception\TransferException
 * or child exception class is thrown.
 *
 * Guzzle exceptions should be used to catch transport
 * exceptions like a request timeout, DNS error etc.
 *
 *
 * Application errors:
 *
 * The server may send HTTP errors with a JSON body in
 * order to send application error messages with a well
 * defined structure.
 *
 * If the client detects a HTTP status >= 400 and a JSON
 * content type, it tries to parse a json object in the
 * following format:
 * {
 *   "message" : "mandatory string",
 *   "details" : "optional string containing debugging information"
 *   "request_id" : "optional string representing a log token"
 * }
 *
 * Server application error messages are thrown as a
 * TS\Web\JsonClient\Exception\ServerMessageException
 *
 * The message, details and request_id are available via
 * getter-methods on the ServerMessageException object.
 *
 *
 * Unexpected response errors:
 *
 * Client methods may parse the response. If the response
 * content does not have the expected type or format, a
 * TS\Web\JsonClient\Exception\UnexpectedResponseException
 * is thrown. Exceptions of this type mean that the contract
 * between server and client is broken and that code must be
 * fixed on either side.
 *
 *
 * Request payload errors:
 *
 * Client methods may take arguments and serialize them into
 * JSON. This can result in an exception implementing
 * Symfony\Component\Serializer\Exception\ExceptionInterface
 * or another exception the client may choose to throw in this
 * case, like an \InvalidArgumentException.
 *
 *
 */
abstract class AbstractApiClient
{


    /** @var Client */
    protected $http;

    /** @var SerializerInterface */
    private $serializer;


    public function __construct(array $options = [], SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $config = $resolver->resolve($options);

        $stack = $config['handler'];
        $this->configureMiddleware($stack, $config);
        $this->http = $this->createClient($config);
    }


    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'handler' => function (Options $options) {
                return HandlerStack::create();
            },
            RequestOptions::HEADERS => [
                'Accept-Encoding' => 'gzip',
                'Accept' => 'application/json'
            ]
        ]);
        $resolver->setDefined(['base_uri']);
        $resolver->setAllowedTypes('handler', [HandlerStack::class]);
        $resolver->setAllowedTypes('base_uri', 'string');
        $resolver->setAllowedTypes(RequestOptions::HEADERS, 'array');


        $resolver->setDefined([
            RequestOptions::ALLOW_REDIRECTS,
            RequestOptions::AUTH,
            RequestOptions::BODY,
            RequestOptions::CERT,
            RequestOptions::COOKIES,
            RequestOptions::CONNECT_TIMEOUT,
            RequestOptions::DEBUG,
            RequestOptions::DECODE_CONTENT,
            RequestOptions::DELAY,
            RequestOptions::EXPECT,
            RequestOptions::FORM_PARAMS,
            RequestOptions::HEADERS,
            RequestOptions::HTTP_ERRORS,
            RequestOptions::IDN_CONVERSION,
            RequestOptions::JSON,
            RequestOptions::MULTIPART,
            RequestOptions::ON_HEADERS,
            RequestOptions::ON_STATS,
            RequestOptions::PROGRESS,
            RequestOptions::PROXY,
            RequestOptions::QUERY,
            RequestOptions::SINK,
            RequestOptions::SYNCHRONOUS,
            RequestOptions::SSL_KEY,
            RequestOptions::STREAM,
            RequestOptions::VERIFY,
            RequestOptions::TIMEOUT,
            RequestOptions::READ_TIMEOUT,
            RequestOptions::VERSION,
            RequestOptions::FORCE_IP_RESOLVE
        ]);
    }


    protected function configureMiddleware(HandlerStack $stack, array $options): void
    {
        $stack->before('http_errors', function (callable $handler) {
            return new ResponseExpectationMiddleware($handler);
        }, 'expect_response');

        $stack->after('expect_response', function (callable $handler) {
            return new DeserializeResponseMiddleware($handler, $this->serializer);
        }, 'deserialize_response');

        $stack->push(function (callable $handler) {
            return new SerializeRequestBodyMiddleware($handler, $this->serializer);
        }, 'serialize_request_body');

        $stack->push(function (callable $handler) {
            return new ServerMessageMiddleware($handler);
        }, 'error_message');
    }


    protected function createClient(array $config): Client
    {
        return new Client($config);
    }


}
