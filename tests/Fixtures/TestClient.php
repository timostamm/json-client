<?php
/**
 * Created by PhpStorm.
 * User: ts
 * Date: 15.05.18
 * Time: 14:16
 */

namespace TS\Web\JsonClient\Fixtures;


use TS\Web\JsonClient\AbstractApiClient;
use TS\Web\JsonClient\Exception\ResponseExpector;

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
        $this->http->get('json-response', [
            'expect_response' => function (ResponseExpector $expectation) {
                $expectation->expectType('application/json');
            }
        ]);
    }


    public function getPayload(): Payload
    {
        return $this->http->get('get-payload', [
            'deserialize_to' => Payload::class
        ]);
    }


}