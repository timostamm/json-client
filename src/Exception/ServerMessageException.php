<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 11.05.18
 * Time: 12:03
 */

namespace TS\Web\JsonClient\Exception;


use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class ServerMessageException extends BadResponseException
{


    private $details;
    private $requestId;


    public function __construct(
        string $message,
        string $details = null,
        string $requestId = null,
        RequestInterface $request,
        ResponseInterface $response = null,
        \Exception $previous = null,
        array $handlerContext = []
    )
    {
        parent::__construct($message, $request, $response, $previous, $handlerContext);
        $this->details = $details;
        $this->requestId = $requestId;
    }


    /**
     * @return string|NULL
     */
    public function getDetails(): ?string
    {
        return $this->details;
    }


    /**
     * @return string|NULL
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }


}
