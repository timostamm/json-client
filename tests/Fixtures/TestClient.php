<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 15.05.18
 * Time: 14:16
 */

namespace TS\Web\JsonClient\Fixtures;


use TS\Web\JsonClient\AbstractApiClient;

class TestClient extends AbstractApiClient
{


    public function getBodyString(): string
    {
        return $this->http->get('body-string')->getBody()->getContents();
    }


    public function sendPayload(Payload $payload): void
    {
        $this->http->post('send-payload', [
            'data' => $payload
        ]);
    }


    public function getJsonResponse(): void
    {
        $response = $this->http->get('json-response');
        $this->expectResponseType('application/json', $response);
    }


    public function getPayload(): Payload
    {
        $response = $this->http->get('get-payload');
        return $this->deserializeResponse($response, Payload::class);
    }


}