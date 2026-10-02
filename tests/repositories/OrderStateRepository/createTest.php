<?php

namespace PayPlug\tests\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_state_repository
 */
class createTest extends BaseOrderStateRepository
{
    private $state = [
        'cfg' => 'PS_OS_REFUND',
        'template' => null,
        'name' => [
            'en' => 'Refunded',
            'fr' => 'Remboursé',
            'es' => 'Reembolsado',
            'it' => 'Rimborsato',
        ],
    ];

    public function testWhenConfigStateIsNativeThenItIsReused()
    {
        $native_state = $this->getOrderState(7, '');

        $this->configuration->shouldReceive('getValue')->with('order_state_refund')->andReturn(false);
        $this->configuration->shouldReceive('getValue')->with('PS_OS_REFUND')->andReturn('7');
        $this->order_state_adapter->shouldReceive('get')->with(7)->andReturn($native_state);
        $this->validate->shouldReceive('validate')->andReturn(true);
        $this->order_state_model_repository->shouldNotReceive('getByName');
        $this->configuration->shouldReceive('set')->once()->with('order_state_refund', '7')->andReturn(true);

        $this->assertTrue($this->repository->create('refund', $this->state, false));
    }

    public function testWhenConfigStateBelongsToAnotherModuleThenItIsNotReused()
    {
        $foreign_state = $this->getOrderState(7, 'ps_checkout');
        $own_state = $this->getOrderState(12, 'payplug');

        $this->configuration->shouldReceive('getValue')->with('order_state_refund')->andReturn(false);
        $this->configuration->shouldReceive('getValue')->with('PS_OS_REFUND')->andReturn('7');
        $this->order_state_adapter->shouldReceive('get')->with(7)->andReturn($foreign_state);
        $this->order_state_adapter->shouldReceive('get')->with(12)->andReturn($own_state);
        $this->validate->shouldReceive('validate')->andReturn(true);
        $this->order_state_model_repository->shouldReceive('getByName')
            ->once()
            ->with($this->state['name'], false)
            ->andReturn(12);
        $this->configuration->shouldReceive('set')->once()->with('order_state_refund', '12')->andReturn(true);
        $this->configuration->shouldNotReceive('set')->with('order_state_refund', '7');

        $this->assertTrue($this->repository->create('refund', $this->state, false));
    }
}
