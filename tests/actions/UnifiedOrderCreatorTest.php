<?php

namespace PayPlug\tests\actions;

use PayPlug\src\actions\UnifiedOrderCreator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group unified_order_creator
 */
class UnifiedOrderCreatorTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testCreateFromOutcomePreservesSecureKeyWhenOrderAlreadyExists()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $cart_adapter = \Mockery::mock('CartAdapter');
        $context_adapter = \Mockery::mock('ContextAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $validator = \Mockery::mock('OrderValidator');

        $existing_order = (object) [
            'id' => 99,
            'secure_key' => 'order_secure_key',
        ];
        $cart = (object) [
            'id' => 42,
            'secure_key' => 'cart_secure_key',
        ];
        $context = (object) ['link' => new class() {
            public function getPageLink($page, $ssl, $lang, $params)
            {
                return 'https://shop.example/order-confirmation?key=' . $params['key'] . '&id_order=' . $params['id_order'];
            }
        }];

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $dependencies->shouldReceive('getValidators')->andReturn(['order' => $validator]);

        $plugin->shouldReceive('getOrder')->andReturn($order_adapter);
        $plugin->shouldReceive('getCart')->andReturn($cart_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context_adapter);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);

        $order_adapter->shouldReceive('getIdByCartId')->with(42)->andReturn(99);
        $order_adapter->shouldReceive('get')->with(99)->andReturn($existing_order);
        $validator->shouldReceive('isCreated')->with($existing_order, 42)->andReturn(['result' => true]);
        $cart_adapter->shouldReceive('get')->with(42)->andReturn($cart);
        $context_adapter->shouldReceive('get')->andReturn($context);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->id = 17;

        $result = UnifiedOrderCreator::createFromOutcome(
            $dependencies,
            42,
            'op_123',
            '0000_SUCCESS',
            'PAID',
            1234
        );
        $this->assertTrue($result['result']);
        $this->assertStringContainsString('key=order_secure_key', $result['redirect_url']);
        $this->assertStringContainsString('id_order=99', $result['redirect_url']);
    }
}
