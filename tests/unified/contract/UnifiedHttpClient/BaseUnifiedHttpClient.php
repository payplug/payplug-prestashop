<?php

namespace PayPlug\tests\unified\contract\UnifiedHttpClient;

use PayPlug\src\unified\contract\UnifiedHttpClient;
use PHPUnit\Framework\TestCase;

abstract class BaseUnifiedHttpClient extends TestCase
{
    protected $client;

    public function setUp(): void
    {
        parent::setUp();
        $this->setDotenvLoaded(true);
        unset($_ENV['SSL_VERIFYPEER'], $_ENV['SSL_VERIFYHOST']);
        $this->client = \Mockery::mock(UnifiedHttpClient::class)
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

    protected function setDotenvLoaded($value)
    {
        $property = new \ReflectionProperty(UnifiedHttpClient::class, 'dotenvLoaded');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
}
