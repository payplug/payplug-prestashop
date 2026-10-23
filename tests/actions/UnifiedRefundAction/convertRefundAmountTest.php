<?php

namespace PayPlug\tests\actions\UnifiedRefundAction;

use PayPlug\src\actions\UnifiedRefundAction;
use PayPlug\src\utilities\helpers\AmountHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group unified_refund_action
 */
class convertRefundAmountTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testConvertsATwoDecimalCurrencyAmountToCents()
    {
        $action = $this->mockAction('USD');

        $this->assertSame(1999, $action->convertRefundAmount(42, '19.99'));
    }

    public function testKeepsAZeroDecimalCurrencyAmountAsIs()
    {
        $action = $this->mockAction('JPY');

        $this->assertSame(1000, $action->convertRefundAmount(42, '1000'));
    }

    public function testReturnsZeroForAThreeDecimalCurrency()
    {
        $action = $this->mockAction('KWD');

        $this->assertSame(0, $action->convertRefundAmount(42, '12.345'));
    }

    public function testReturnsZeroForANonNumericAmount()
    {
        $action = $this->mockAction('USD');

        $this->assertSame(0, $action->convertRefundAmount(42, '12abc'));
    }

    public function testReturnsZeroForAnUnknownOrder()
    {
        $action = $this->mockAction('USD', false);

        $this->assertSame(0, $action->convertRefundAmount(42, '19.99'));
    }

    private function mockAction($iso_code, $order_loaded = true)
    {
        $action = (new \ReflectionClass(UnifiedRefundAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $validate_adapter = \Mockery::mock('ValidateAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $constant_adapter = \Mockery::mock('ConstantAdapter');

        $order = new \stdClass();
        $order->id = 42;
        $order->id_currency = 2;
        $order->module = 'payplug';

        $currency = new \stdClass();
        $currency->iso_code = $iso_code;

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getHelpers')->andReturn(['amount' => new AmountHelper($dependencies)]);

        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getValidate')->andReturn($validate_adapter);
        $plugin->shouldReceive('getCurrency')->andReturn($currency_adapter);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class);
        $plugin->shouldReceive('getConstant')->andReturn($constant_adapter);

        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $factory->shouldReceive('createLogger')->andReturn($logger);
        $logger->shouldReceive('error');

        $order_adapter->shouldReceive('get')->with(42)->andReturn($order);
        $validate_adapter->shouldReceive('validate')->with('isLoadedObject', $order)->andReturn($order_loaded);
        $currency_adapter->shouldReceive('get')->with(2)->andReturn($currency);

        $configuration_class->shouldReceive('getValue')->with('PS_PRICE_ROUND_MODE')->andReturn(2);
        $constant_adapter->shouldReceive('get')->andReturnUsing(function ($name) {
            $constants = [
                'PS_ROUND_UP' => 0,
                'PS_ROUND_DOWN' => 1,
                'PS_ROUND_HALF_UP' => 2,
                'PS_ROUND_HALF_DOWN' => 3,
                'PS_ROUND_HALF_EVEN' => 4,
                'PS_ROUND_HALF_ODD' => 5,
            ];

            return $constants[$name];
        });

        $action->dependencies = $dependencies;

        return $action;
    }
}
