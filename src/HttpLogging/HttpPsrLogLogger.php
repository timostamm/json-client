<?php /** @noinspection PhpUndefinedMethodInspection */

/**
 * Created by PhpStorm.
 * User: ts
 * Date: 10.08.18
 * Time: 12:37
 */

namespace TS\Web\JsonClient\HttpLogging;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class HttpPsrLogLogger implements HttpLoggerInterface
{

    const PSR_LOGGER_INTERFACE = 'Psr\Log\LoggerInterface';

    private $psrLogger;
    private $prefix;
    private $logLevelSuccess;
    private $logLevelFailure;
    private $logLevelStart;


    public function __construct($psrLogger, string $prefix = null, string $logLevelSuccess = 'info', string $logLevelFailure = 'error', string $logLevelStart = null)
    {
        if (!is_subclass_of($psrLogger, self::PSR_LOGGER_INTERFACE, false)) {
            $msg = 'Expected psrLogger to implement ' . self::PSR_LOGGER_INTERFACE;
            throw new \InvalidArgumentException($msg);
        }
        $this->psrLogger = $psrLogger;
        $this->prefix = is_null($prefix) ? '' : ($prefix . ' ');
        $this->logLevelSuccess = $logLevelSuccess;
        $this->logLevelFailure = $logLevelFailure;
        $this->logLevelStart = $logLevelStart;
    }


    public function logStart(RequestInterface $request, array $requestOptions): RequestInterface
    {
        if (is_null($this->logLevelStart)) {
            return $request;
        }

        $message = $this->buildRequestString($request);

        $this->psrLogger->log($this->logLevelSuccess, $this->prefix . $message, [
            'request' => $request,
            'request_options' => $requestOptions
        ]);
        return $request;
    }


    public function logSuccess(RequestInterface $request, ResponseInterface $response, array $requestOptions): void
    {

        $message = $this->buildRequestString($request) . ' → ' . $this->buildResponseString($response);

        $this->psrLogger->log($this->logLevelSuccess, $this->prefix . $message, [
            'request' => $request,
            'response' => $response,
            //'request_options' => $requestOptions
        ]);
    }


    public function logFailure(RequestInterface $request, ?ResponseInterface $response, \Throwable $reason, array $requestOptions): void
    {

        $message = $response
            ? sprintf('%s → %s (%s)', $this->buildRequestString($request), $this->buildResponseString($response), $this->buildExceptionString($reason))
            : sprintf('%s → %s', $this->buildRequestString($request), $this->buildExceptionString($reason));

        $this->psrLogger->log($this->logLevelFailure, $this->prefix . $message, [
            'request' => $request,
            'response' => $response,
            'reason' => $reason,
            //'request_options' => $requestOptions
        ]);
    }


    protected function buildRequestString(RequestInterface $request): string
    {
        return sprintf('%s %s', $request->getMethod(), $request->getUri());
    }

    protected function buildResponseString(ResponseInterface $response): string
    {
        $str = 'HTTP ' . $response->getStatusCode();
        $phrase = $response->getReasonPhrase();
        if (!empty($phrase)) {
            $str .= ' ' . $phrase;
        }
        return $str;
    }

    protected function buildExceptionString(\Throwable $throwable): string
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $ref = new \ReflectionClass($throwable);
        $name = $ref->getShortName();
        $msg = $throwable->getMessage();
        $str = $name;
        if (!empty($msg)) {
            $str .= ': ' . $msg;
        }
        return $str;
    }


}
