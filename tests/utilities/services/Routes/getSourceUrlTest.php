<?php

namespace PayPlug\tests\utilities\services\Routes;

use PayPlug\src\utilities\services\Routes;
use PHPUnit\Framework\TestCase;

// getHostedFieldsUrl() logs via \PrestaShopLogger when HOSTED_FIELDS_URL isn't
// configured; nothing in this test suite's bootstrap defines PrestaShop core
// classes, so stub the one static call this test path actually reaches.
require_once __DIR__ . '/../../../stubs/PrestaShopLogger.php';

/**
 * @group unit
 * @group service
 * @group routes_service
 */
class getSourceUrlTest extends TestCase
{
    public $service;
    public $dependencies;

    public function setUp(): void
    {
        $this->service = \Mockery::mock(Routes::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->service->dependencies = $this->dependencies;

        // getHostedFieldsUrl()/getApiUrl()/etc. only skip real dotenv parsing
        // when this private static flag is already true; forcing it here
        // makes $_ENV the single source of truth for these tests regardless
        // of whether a real payplugroutes/.env happens to exist on whatever
        // machine runs the suite (it does on some developer setups, per the
        // module's own dev-proxy docs, which would otherwise make these
        // tests non-deterministic).
        $this->setDotenvLoaded(true);
        unset($_ENV['HOSTED_FIELDS_URL']);
    }

    public function tearDown(): void
    {
        $this->setDotenvLoaded(false);
        unset($_ENV['HOSTED_FIELDS_URL']);
        \Mockery::close();
    }

    public function testReturnsExpectedKeysBuiltFromEachHelper()
    {
        $this->service->shouldReceive('getApiUrl')->once()->andReturn('https://api.test');
        $this->service->shouldReceive('getCDNUrl')->once()->andReturn('https://cdn.test');
        $this->service->shouldReceive('getOneyLoaderUrl')->once()->andReturn('https://oney.test/loader.js');
        $this->service->shouldReceive('getHostedFieldsUrl')->once()->andReturn('https://hosted-fields.test/sdk.js');

        $routes = $this->service->getSourceUrl();

        $this->assertSame(
            [
                'applepay' => 'https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js',
                'embedded' => 'https://api.test/js/1/form.latest.js',
                'integrated' => 'https://cdn.test/js/integrated-payment/v1@1/index.js',
                'oney' => 'https://oney.test/loader.js',
                'hosted_fields' => 'https://hosted-fields.test/sdk.js',
            ],
            $routes
        );
    }

    public function testHostedFieldsKeyIsEmptyWhenSdkUrlIsNotConfigured()
    {
        $this->service->shouldReceive('getApiUrl')->andReturn('https://api.test');
        $this->service->shouldReceive('getCDNUrl')->andReturn('https://cdn.test');
        $this->service->shouldReceive('getOneyLoaderUrl')->andReturn('https://oney.test/loader.js');

        $routes = $this->service->getSourceUrl();

        $this->assertSame('', $routes['hosted_fields']);
    }

    public function testGetHostedFieldsUrlReturnsEnvValueWhenConfigured()
    {
        $_ENV['HOSTED_FIELDS_URL'] = 'https://hosted-fields.example/sdk.js';

        $this->assertSame('https://hosted-fields.example/sdk.js', $this->service->getHostedFieldsUrl());
    }

    public function testGetHostedFieldsUrlReturnsEmptyStringWhenNotConfigured()
    {
        $this->assertSame('', $this->service->getHostedFieldsUrl());
    }

    private function setDotenvLoaded($value)
    {
        $property = new \ReflectionProperty(Routes::class, 'dotenvLoaded');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
}
