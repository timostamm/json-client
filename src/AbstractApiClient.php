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
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Serializer\SerializerInterface;
use TS\Web\JsonClient\Exception\UnexpectedResponseException;
use TS\Web\JsonClient\Middleware\SerializeRequestBodyMiddleware;
use TS\Web\JsonClient\Middleware\ServerMessageMiddleware;


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
        $resolver->setDefined(['base_uri', 'handler']);

        $resolver->setDefault(RequestOptions::HEADERS, [
            'Accept-Encoding' => 'gzip',
            'Accept' => 'application/json'
        ]);

        $resolver->setAllowedTypes('base_uri', 'string');
        $resolver->setDefault('handler', function(Options $options){
            return HandlerStack::create();
        });
        $resolver->setAllowedTypes('handler', [HandlerStack::class]);

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


    protected function configureMiddleware(HandlerStack $stack, array $options):void
    {
        $stack->push(function (callable $handler) {
            return new ServerMessageMiddleware($handler);
        }, 'error_message');

        $stack->push(function (callable $handler) {
            return new SerializeRequestBodyMiddleware($handler, $this->serializer);
        }, 'serialize_request_body');
    }


    protected function createClient(array $config): Client
    {
        return new Client($config);
    }


    /**
     * Ensure that the response has the expected content type.
     * If the expected type contains a charset, it must match.
     * If the expected type does not contain a charset, the
     * charset is ignored when matching the type.
     *
     * @param string $expectedType
     * @param ResponseInterface $response
     * @throw UnexpectedResponseException if the response content type does not match
     */
    protected function expectResponseType(string $expectedType, ResponseInterface $response): void
    {
        $actualType = $response->getHeaderLine('Content-Type');
        if ($actualType === $expectedType) {
            return;
        }
        $actual = explode('; charset=', $actualType);
        $expected = explode('; charset=', $expectedType);
        if (empty($expected[1]) && $expected[0] === $actual[0]) {
            return;
        }
        $msg = sprintf('Expected response content type to be %s, got %s instead.', $expectedType, $actualType);
        throw new UnexpectedResponseException($msg, $response);
    }


    /**
     * Deserialize the request body into the given type.
     *
     * @param ResponseInterface $response
     * @param string $type
     * @param array $context
     * @return mixed
     * @throws UnexpectedResponseException if the response is not application/json or deserialization failed
     */
    protected function deserializeResponse(ResponseInterface $response, string $type, array $context = [])
    {
        $context = array_replace([
            'allow_extra_attributes' => false
        ], $context);

        $this->expectResponseType('application/json', $response);
        $data = $response->getBody()->getContents();
        try {

            $object = $this->serializer
                ->deserialize($data, $type, 'json', $context);

            return $object;

        } catch (\Exception $exception) {
            $msg = sprintf('Failed to deserialize response body to type %s: %s', $type, $exception->getMessage());
            throw new UnexpectedResponseException($msg, $response, $exception);
        }
    }


}
