<?php

namespace PayPlug\tests\unified\contract\UnifiedHttpClient;

use PayPlug\src\unified\contract\UnifiedHttpClient;

/**
 * @group unit
 * @group unified
 */
class getTest extends BaseUnifiedHttpClient
{
    public function testGetSendsNoBodyWithGivenHeaders()
    {
        $this->client->shouldReceive('send')
            ->once()
            ->withArgs(function ($url, $body, $headerLines) {
                return 'https://example.test/payments/pay_1' === $url
                    && null === $body
                    && in_array('Authorization: Bearer abc', $headerLines, true);
            })
            ->andReturn(['status' => 200, 'body' => '{"id":"pay_1"}']);

        $result = $this->client->get('https://example.test/payments/pay_1', ['Authorization' => 'Bearer abc']);

        $this->assertSame(['status' => 200, 'body' => '{"id":"pay_1"}'], $result);
    }

    public function testGetReturnsStatusZeroAndTheCurlErrorWhenTheTransportFails()
    {
        // Uses a real (non-mocked) client so that send()'s actual curl_exec() runs.
        // An empty URL is rejected by cURL before any connection attempt, so this
        // fails immediately and needs no network access.
        $client = new UnifiedHttpClient();

        $result = $client->get('', []);

        $this->assertSame(0, $result['status']);
        $this->assertStringStartsWith('cURL transport error: ', $result['body']);
    }
}
