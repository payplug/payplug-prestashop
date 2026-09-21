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
        unset($_ENV['UNIFIED_API_BASE_URL'], $_ENV['UPC_IDENTITY_PROVIDER_URL']);
        \PrestaShopLogger::$logs = [];
    }

    public function tearDown(): void
    {
        $this->setDotenvLoaded(false);
        unset($_ENV['UNIFIED_API_BASE_URL'], $_ENV['UPC_IDENTITY_PROVIDER_URL']);
        \PrestaShopLogger::$logs = [];
    }

    public function testReturnsUnifiedApiUrlFromEnvWhenConfigured()
    {
        $_ENV['UNIFIED_API_BASE_URL'] = 'https://unified-api.example';

        $this->assertSame('https://unified-api.example', $this->service->getUnifiedApiUrl());
        $this->assertSame([], \PrestaShopLogger::$logs);
    }

    public function testReturnsEmptyStringAndLogsWhenUnifiedApiUrlIsMissing()
    {
        $this->assertSame('', $this->service->getUnifiedApiUrl());
        $this->assertCount(1, \PrestaShopLogger::$logs);
        $this->assertSame('PayPlug: UNIFIED_API_BASE_URL is not configured — Unified Hosted Fields payments will fail.', \PrestaShopLogger::$logs[0]['message']);
    }

    public function testReturnsIdentityProviderUrlFromEnvWhenConfigured()
    {
        $_ENV['UPC_IDENTITY_PROVIDER_URL'] = 'https://identity.example';

        $this->assertSame('https://identity.example', $this->service->getIdentityProviderUrl());
        $this->assertSame([], \PrestaShopLogger::$logs);
    }

    public function testReturnsEmptyStringAndLogsWhenIdentityProviderUrlIsMissing()
    {
        $this->assertSame('', $this->service->getIdentityProviderUrl());
        $this->assertCount(1, \PrestaShopLogger::$logs);
        $this->assertSame('PayPlug: UPC_IDENTITY_PROVIDER_URL is not configured — Unified Hosted Fields payments will fail.', \PrestaShopLogger::$logs[0]['message']);
    }

    private function setDotenvLoaded($value)
    {
        $property = new \ReflectionProperty(Routes::class, 'dotenvLoaded');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
}
