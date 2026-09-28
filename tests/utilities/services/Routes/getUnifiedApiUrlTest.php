<?php

namespace PayPlug\tests\utilities\services\Routes;

use PayPlug\src\utilities\services\Routes;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../stubs/PrestaShopLogger.php';

/**
 * @group unit
 * @group service
 * @group routes_service
 */
class getUnifiedApiUrlTest extends TestCase
{
    /** @var Routes */
    private $service;

    public function setUp(): void
    {
        $this->service = new Routes();
        $this->setDotenvLoaded(true);
        unset($_ENV['UNIFIED_API_BASE_URL']);
        \PrestaShopLogger::$logs = [];
    }

    public function tearDown(): void
    {
        $this->setDotenvLoaded(false);
        unset($_ENV['UNIFIED_API_BASE_URL']);
        \PrestaShopLogger::$logs = [];
    }

    public function testReturnsUnifiedApiUrlFromEnvWhenConfigured()
    {
        $_ENV['UNIFIED_API_BASE_URL'] = 'https://unified-api.example';

        $this->assertSame('https://unified-api.example', $this->service->getUnifiedApiUrl());
        $this->assertSame([], \PrestaShopLogger::$logs);
    }

    public function testReturnsDefaultUnifiedApiUrlWhenMissing()
    {
        // No dedicated identity-provider concept/fallback: auth goes through getApiUrl()
        // directly (see Routes::getApiUrl()). The Unified API's own base URL now shares
        // getApiUrl()'s production default too.
        $this->assertSame('https://api.payplug.com', $this->service->getUnifiedApiUrl());
        $this->assertSame([], \PrestaShopLogger::$logs);
    }

    private function setDotenvLoaded($value)
    {
        $property = new \ReflectionProperty(Routes::class, 'dotenvLoaded');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
}
