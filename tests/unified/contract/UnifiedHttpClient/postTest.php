<?php

namespace PayPlug\tests\unified\contract\UnifiedHttpClient;

/**
 * @group unit
 * @group unified
 */
class postTest extends BaseUnifiedHttpClient
{
    public function testPostSendsFormEncodedBodyWithContentTypeHeader()
    {
        $this->client->shouldReceive('send')
            ->once()
            ->withArgs(function ($url, $body, $headerLines) {
                return 'https://example.test/token' === $url
                    && 'grant_type=client_credentials' === $body
                    && in_array('Content-Type: application/x-www-form-urlencoded', $headerLines, true);
            })
            ->andReturn(['status' => 200, 'body' => '{"access_token":"abc"}']);

        $result = $this->client->post('https://example.test/token', ['grant_type' => 'client_credentials']);

        $this->assertSame(['status' => 200, 'body' => '{"access_token":"abc"}'], $result);
    }
}
