<?php

namespace PayPlug\tests\utilities\services\Routes;

use PayPlug\src\utilities\services\Routes;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group service
 * @group routes_service
 */
class getScalapayWidgetEnvironmentTest extends TestCase
{
    /** @var Routes */
    private $service;

    public function setUp(): void
    {
        $this->service = new Routes();
        $this->setDotenvLoaded(true);
        unset($_ENV['SCALAPAY_WIDGET_ENVIRONMENT']);
    }

    public function tearDown(): void
    {
        $this->setDotenvLoaded(false);
        unset($_ENV['SCALAPAY_WIDGET_ENVIRONMENT']);
    }

    public function testNoEnvironmentWhenTheVariableIsUnset()
    {
        $this->assertSame('', $this->service->getScalapayWidgetEnvironment());
    }

    public function environmentProvider()
    {
        yield 'integration' => ['integration', 'integration'];

        yield 'surrounding whitespace' => ["  integration\n", 'integration'];

        yield 'upper case' => ['Integration', 'integration'];

        yield 'empty' => ['', ''];

        yield 'blank' => ['   ', ''];

        yield 'production' => ['production', ''];

        yield 'staging' => ['staging', ''];

        yield 'development' => ['development', ''];

        yield 'dev prefix' => ['dev-foo', ''];

        yield 'markup' => ['integration"><script>', ''];
    }

    /**
     * @dataProvider environmentProvider
     *
     * @param string $value
     * @param string $expected
     */
    public function testOnlyTheIntegrationEnvironmentIsReturned($value, $expected)
    {
        $_ENV['SCALAPAY_WIDGET_ENVIRONMENT'] = $value;

        $this->assertSame($expected, $this->service->getScalapayWidgetEnvironment());
    }

    private function setDotenvLoaded($value)
    {
        $property = new \ReflectionProperty(Routes::class, 'dotenvLoaded');
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }
}
