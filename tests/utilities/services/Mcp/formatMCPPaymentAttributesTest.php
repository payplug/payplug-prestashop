<?php

namespace PayPlug\tests\utilities\services\Mcp;

use PayPlug\src\utilities\services\Mcp;
use PHPUnit\Framework\TestCase;

/**
 * Mcp uses PHP 8 attributes and cannot be loaded on older PHP versions.
 *
 * @requires PHP >= 8.0
 *
 * @group unit
 * @group service
 * @group mcp_service
 */
class formatMCPPaymentAttributesTest extends TestCase
{
    private $sandbox_mode = 1;
    private $requested_live_bearer;
    private $context;
    private $link;
    private $service;

    public function setUp(): void
    {
        $configuration = \Mockery::mock('Configuration');
        $configuration->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturnUsing(function () {
                return $this->sandbox_mode;
            });

        $api_service = \Mockery::mock('ApiService');
        $api_service->shouldReceive('getMcpApiBearer')
            ->andReturnUsing(function ($is_live) {
                $this->requested_live_bearer = $is_live;

                return 'bearer';
            });

        $module_instance = \Mockery::mock('ModuleInstance');
        $module_instance->shouldReceive('getService')
            ->with('payplug.utilities.service.api')
            ->andReturn($api_service);

        $module = \Mockery::mock('Module');
        $module->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module_instance);

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive([
            'getConfigurationClass' => $configuration,
            'getModule' => $module,
        ]);

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->shouldReceive(['getPlugin' => $plugin]);

        $this->link = \Mockery::mock('Link');

        $this->context = new \stdClass();
        $this->context->link = $this->link;
        $this->context->shop = \Mockery::mock('Shop');
        $this->context->shop->shouldReceive('getBaseURL')->andReturn('https://shop.test/');

        $this->service = \Mockery::mock(Mcp::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $dependencies_property = new \ReflectionProperty(Mcp::class, 'dependencies');
        $dependencies_property->setAccessible(true);
        $dependencies_property->setValue($this->service, $dependencies);

        $context_property = new \ReflectionProperty(Mcp::class, 'context');
        $context_property->setAccessible(true);
        $context_property->setValue($this->service, $this->context);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testUrlsAreBuiltWithModuleLinks()
    {
        $this->link->shouldReceive('getModuleLink')
            ->once()
            ->with('payplug', 'validation', ['ps' => 1, 'cartid' => 42], true)
            ->andReturn('RETURN_URL');
        $this->link->shouldReceive('getModuleLink')
            ->once()
            ->with('payplug', 'validation', ['ps' => 2, 'cartid' => 42], true)
            ->andReturn('CANCEL_URL');
        $this->link->shouldReceive('getModuleLink')
            ->once()
            ->with('payplug', 'ipn', [], true)
            ->andReturn('NOTIFICATION_URL');

        $attributes = $this->format(['cart_id' => 42]);

        $this->assertSame(
            [
                'return' => 'RETURN_URL',
                'cancel' => 'CANCEL_URL',
                'notification' => 'NOTIFICATION_URL',
            ],
            $attributes['urls']
        );
    }

    public function testCartIdIsCastToInt()
    {
        $this->mockModuleLinks();

        $attributes = $this->format(['cart_id' => '42']);

        $this->assertSame(
            'https://shop.test/module/payplug/validation?ps=1&cartid=42',
            $attributes['urls']['return']
        );
    }

    public function testUrlsTargetExistingFrontControllers()
    {
        $this->mockModuleLinks();

        $attributes = $this->format(['cart_id' => 42]);

        foreach ($attributes['urls'] as $url) {
            $this->assertStringNotContainsString('module/payplug/notification', $url);
            $this->assertStringNotContainsString('module/payplug/cancel', $url);
        }
    }

    public function testOtherAttributesAreKept()
    {
        $this->mockModuleLinks();

        $attributes = $this->format(['cart_id' => 42, 'amount' => 4242, 'currency_iso_code' => 'EUR']);

        $this->assertArrayNotHasKey('cart_id', $attributes);

        $this->assertSame(4242, $attributes['amount']);
        $this->assertSame('EUR', $attributes['currency_iso_code']);
        $this->assertSame('email_link', $attributes['payment_method']);
        $this->assertSame('bearer', $attributes['api_bearer']);
        $this->assertSame(['source' => 'powered by MCP Payplug'], $attributes['metadata']);
        $this->assertSame([], $attributes['context']);
    }

    /**
     * @dataProvider sandboxModeProvider
     *
     * @param mixed $sandbox_mode
     * @param bool $is_live
     */
    public function testApiBearerFollowsSandboxMode($sandbox_mode, $is_live)
    {
        $this->sandbox_mode = $sandbox_mode;
        $this->mockModuleLinks();

        $this->format(['cart_id' => 42]);

        $this->assertSame($is_live, $this->requested_live_bearer);
    }

    public function sandboxModeProvider()
    {
        return [
            'sandbox' => [1, false],
            'live' => [0, true],
        ];
    }

    /**
     * @dataProvider missingCartIdProvider
     */
    public function testThrowsWithoutCartId(array $attributes)
    {
        $this->link->shouldNotReceive('getModuleLink');

        $this->expectException(\InvalidArgumentException::class);

        $this->format($attributes);
    }

    public function missingCartIdProvider()
    {
        return [
            'no cart_id' => [[]],
            'null cart_id' => [['cart_id' => null]],
        ];
    }

    private function mockModuleLinks()
    {
        $this->link->shouldReceive('getModuleLink')
            ->andReturnUsing(function ($module, $controller, array $params = [], $ssl = null) {
                $query = $params ? '?' . http_build_query($params) : '';

                return 'https://shop.test/module/' . $module . '/' . $controller . $query;
            });
    }

    /**
     * @return array
     */
    private function format(array $attributes)
    {
        $method = new \ReflectionMethod(Mcp::class, 'formatMCPPaymentAttributes');
        $method->setAccessible(true);

        return $method->invoke($this->service, $attributes);
    }
}
