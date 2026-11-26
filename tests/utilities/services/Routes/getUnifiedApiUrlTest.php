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
        $this->clearUnifiedApiUrl();
        \PrestaShopLogger::$logs = [];
    }

    public function tearDown(): void
    {
        $this->setDotenvLoaded(false);
        $this->clearUnifiedApiUrl();
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
        // Auth goes through getApiUrl() (api.payplug.com); the Unified API has its own production host.
        $this->assertSame('https://payment.payplug.com', $this->service->getUnifiedApiUrl());
        $this->assertSame([], \PrestaShopLogger::$logs);
    }

    /**
     * @description getEnv() falls back to $_SERVER then getenv(), and an earlier test that loaded
     *              a real payplugroutes/.env (Symfony Dotenv fills $_ENV and $_SERVER) leaves the
     *              value there, so all three sources are cleared
     */
    private function clearUnifiedApiUrl()
    {
        unset($_ENV['UNIFIED_API_BASE_URL'], $_SERVER['UNIFIED_API_BASE_URL']);
        putenv('UNIFIED_API_BASE_URL');
    }

    private function setDotenvLoaded($value)
    {
        $property = new \ReflectionProperty(Routes::class, 'dotenvLoaded');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
}
