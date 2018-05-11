<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 11.05.18
 * Time: 16:07
 */

namespace TS\Web\JsonClient\Exception;


use Psr\Http\Message\ResponseInterface;


class UnexpectedResponseException extends \UnexpectedValueException
{

    private $response;
    private $handlerContext;

    public function __construct($message, ResponseInterface $response = null, \Exception $previous = null, array $handlerContext = [])
    {
        parent::__construct($message, 0, $previous);
        $this->response = $response;
        $this->handlerContext = $handlerContext;
    }


    /**
     * Get the associated response
     *
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Check if a response was received
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return $this->response !== null;
    }

    /**
     * Get contextual information about the error from the underlying handler.
     *
     * The contents of this array will vary depending on which handler you are
     * using. It may also be just an empty array. Relying on this data will
     * couple you to a specific handler, but can give more debug information
     * when needed.
     *
     * @return array
     */
    public function getHandlerContext(): array
    {
        return $this->handlerContext;
    }

}
