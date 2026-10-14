<?php

namespace PayPlug\tests\unified\contract\UnifiedHttpClient;

/**
 * @group unit
 * @group unified
 */
class postJsonTest extends BaseUnifiedHttpClient
{
    public function testPostJsonSendsJsonEncodedBodyWithContentTypeHeader()
    {
        $this->client->shouldReceive('send')
            ->once()
            ->withArgs(function ($url, $body, $headerLines) {
                return 'https://example.test/payments' === $url
                    && '{"amount":1000}' === $body
                    && in_array('Content-Type: application/json', $headerLines, true)
                    && in_array('Authorization: Bearer abc', $headerLines, true);
            })
            ->andReturn(['status' => 201, 'body' => '{"id":"pay_2"}']);

        $result = $this->client->postJson(
            'https://example.test/payments',
            ['amount' => 1000],
            ['Authorization' => 'Bearer abc']
        );

        $this->assertSame(['status' => 201, 'body' => '{"id":"pay_2"}'], $result);
    }
}
