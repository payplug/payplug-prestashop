<?php

namespace PayPlug\tests\actions\UnifiedOrderAction;

/**
 * @group unit
 * @group action
 * @group unified_order_action
 */
class errorUrlTest extends BaseUnifiedOrderAction
{
    public function testBuildsTheCheckoutErrorUrlThroughTheContextLink()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $plugin = \Mockery::mock('Plugin');
        $context_adapter = \Mockery::mock('ContextAdapter');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getContext')->andReturn($context_adapter);
        $context_adapter->shouldReceive('getPageLink')
            ->once()
            ->with('order', [
                'step' => '3',
                'has_error' => '1',
                'modulename' => 'payplug',
            ])
            ->andReturn('https://shop.example/fr/commande?step=3&has_error=1&modulename=payplug');

        $this->assertSame(
            'https://shop.example/fr/commande?step=3&has_error=1&modulename=payplug',
            $this->mockOrderAction($dependencies)->errorUrl()
        );
    }
}
