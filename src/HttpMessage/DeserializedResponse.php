<?php

namespace TS\Web\JsonClient\HttpMessage;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class DeserializedResponse implements ResponseInterface
{
    public function __construct(private ResponseInterface $response, private object|array $data)
    {
    }

    public function getDeserializedData(): object|array
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    /**
     * @inheritDoc
     */
    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->response->withProtocolVersion($version);
    }

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    /**
     * @inheritDoc
     */
    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    /**
     * @inheritDoc
     */
    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->response->withHeader($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->response->withAddedHeader($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function withoutHeader(string $name): MessageInterface
    {
        return $this->response->withoutHeader($name);
    }

    /**
     * @inheritDoc
     */
    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    /**
     * @inheritDoc
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->response->withBody($body);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @inheritDoc
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->response->withStatus($code, $reasonPhrase);
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }
}
