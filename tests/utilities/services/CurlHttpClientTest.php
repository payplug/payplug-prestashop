<?php

namespace PayPlug\tests\utilities\services;

use PayPlug\src\utilities\services\CurlHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group service
 */
class CurlHttpClientTest extends TestCase
{
    private $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->setDotenvLoaded(true);
        unset($_ENV['SSL_VERIFYPEER'], $_ENV['SSL_VERIFYHOST']);
        $this->client = \Mockery::mock(CurlHttpClient::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
    }

    public function tearDown(): void
    {
        \Mockery::close();
        unset($_ENV['SSL_VERIFYPEER'], $_ENV['SSL_VERIFYHOST']);
        $this->setDotenvLoaded(false);
        parent::tearDown();
    }

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

    public function testGetReturnsStatusZeroAndTheCurlErrorWhenTheTransportFails()
    {
        // Uses a real (non-mocked) client so that send()'s actual curl_exec() runs.
        // An empty URL is rejected by cURL before any connection attempt, so this
        // fails immediately and needs no network access.
        $client = new CurlHttpClient();

        $result = $client->get('', []);

        $this->assertSame(0, $result['status']);
        $this->assertStringStartsWith('cURL transport error: ', $result['body']);
    }

    public function testSslVerificationIsEnabledByDefaultWhenTheEnvVarIsUnset()
    {
        unset($_ENV['SSL_VERIFYPEER']);

        $this->assertTrue($this->client->isSslVerificationEnabled('SSL_VERIFYPEER'));
    }

    public function testSslVerificationIsDisabledOnlyByAnExplicitZero()
    {
        $_ENV['SSL_VERIFYPEER'] = '0';

        $this->assertFalse($this->client->isSslVerificationEnabled('SSL_VERIFYPEER'));
    }

    /**
     * @dataProvider nonDisablingValueDataProvider
     *
     * @param mixed $value
     */
    public function testSslVerificationStaysEnabledForAnyValueOtherThanExplicitZero($value)
    {
        $_ENV['SSL_VERIFYHOST'] = $value;

        $this->assertTrue($this->client->isSslVerificationEnabled('SSL_VERIFYHOST'));
    }

    public function nonDisablingValueDataProvider()
    {
        yield ['1'];

        yield ['true'];

        yield [''];
    }

    private function setDotenvLoaded($value)
    {
        $property = new \ReflectionProperty(CurlHttpClient::class, 'dotenvLoaded');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
}
