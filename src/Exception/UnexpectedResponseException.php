<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 11.05.18
 * Time: 16:07
 */

namespace TS\Web\JsonClient\Exception;


use GuzzleHttp\Exception\BadResponseException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class UnexpectedResponseException extends BadResponseException
{

    private $response;
    private $handlerContext;

    public function __construct($message, RequestInterface $request, ResponseInterface $response, \Exception $previous = null, array $handlerContext = [])
    {
        parent::__construct($message, $request, $response, $previous, $handlerContext);
        $this->response = $response;
        $this->handlerContext = $handlerContext;
    }

}
