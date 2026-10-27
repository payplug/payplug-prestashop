<?php

namespace PayPlug\tests\actions\UnifiedOrderAction;

/**
 * @group unit
 * @group action
 * @group unified_order_action
 */
class existingOrderRedirectTest extends BaseUnifiedOrderAction
{
    public function testExistingOrderRedirectReturnsConfirmationUrlWhenOrderAlreadyExists()
    {
        [$dependencies] = $this->mockDependenciesForExistingOrder();

        $result = $this->mockOrderAction($dependencies)->existingOrderRedirect(42);

        $this->assertNotNull($result);
        $this->assertTrue($result['result']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }

    public function testExistingOrderRedirectReturnsNullWhenExistingOrderIsAbandoned()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $validator = \Mockery::mock('OrderValidator');
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $order_class = \Mockery::mock('OrderClass');

        $existing_order = (object) [
            'id' => 99,
            'secure_key' => 'order_secure_key',
            'module' => 'payplug',
            'current_state' => 5,
        ];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getConfigurationClass')->andReturn($configuration_class);
        $configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $plugin->shouldReceive('getOrderClass')->andReturn($order_class);
        $order_class->shouldReceive('getOrderStates')->with(true)->andReturn(['error' => 5, 'paid' => 2, 'pending' => 1]);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(99);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($existing_order);
        $validator->shouldReceive('isCreated')->with($existing_order, 42)->andReturn(['result' => true]);

        $this->assertNull($this->mockOrderAction($dependencies)->existingOrderRedirect(42));
    }

    public function testExistingOrderRedirectReturnsNullWhenNoOrderExistsYet()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $validator = \Mockery::mock('OrderValidator');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);
        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(0);
        $order_adapter->shouldReceive('get')->with(0)->andReturn(null);
        $validator->shouldReceive('isCreated')->with(null, 42)->andReturn(['result' => false]);

        $this->assertNull($this->mockOrderAction($dependencies)->existingOrderRedirect(42));
    }
}
