<?php

namespace PayPlug\tests\actions\ValidationAction;

/**
 * @group unit
 * @group action
 * @group validation_action
 */
class getOrderLinksTest extends BaseValidationAction
{
    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies->name = 'payplug';
        $this->context->shouldReceive('getPageLink')
            ->with('order', ['step' => 3, 'has_error' => 1, 'modulename' => 'payplug'])
            ->andReturn('https://shop.example/order?error');
        $this->context->shouldReceive('getPageLink')
            ->with('order', ['step' => 3])
            ->andReturn('https://shop.example/order?cancel');
    }

    public function testBuildsTheLinksThroughTheContextAdapterWithoutOrder()
    {
        $this->context->shouldReceive('getPageLink')
            ->once()
            ->with('order-confirmation', [])
            ->andReturn('https://shop.example/order-confirmation');

        $this->assertSame([
            'confirm' => 'https://shop.example/order-confirmation',
            'error' => 'https://shop.example/order?error',
            'cancel' => 'https://shop.example/order?cancel',
        ], $this->action->getOrderLinks());
    }

    public function testBuildsTheConfirmationLinkOfTheGivenOrder()
    {
        $order_adapter = \Mockery::mock('OrderAdapter');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $this->plugin->shouldReceive([
            'getOrder' => $order_adapter,
            'getModule' => $module_adapter,
        ]);
        $order_adapter->shouldReceive('get')
            ->with(99)
            ->andReturn((object) ['id_cart' => 42, 'secure_key' => 'order_secure_key']);
        $module_adapter->shouldReceive('getInstanceByName')
            ->with('payplug')
            ->andReturn((object) ['id' => 17]);
        $this->context->shouldReceive('getPageLink')
            ->once()
            ->with('order-confirmation', [
                'id_cart' => 42,
                'id_module' => 17,
                'id_order' => 99,
                'key' => 'order_secure_key',
            ])
            ->andReturn('https://shop.example/order-confirmation?id_order=99');

        $this->assertSame(
            'https://shop.example/order-confirmation?id_order=99',
            $this->action->getOrderLinks(99)['confirm']
        );
    }
}
