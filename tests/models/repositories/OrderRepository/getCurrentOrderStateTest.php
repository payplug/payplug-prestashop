<?php

namespace PayPlug\tests\models\repositories\OrderRepository;

/**
 * @group unit
 * @group repository
 * @group order_repository
 */
class getCurrentOrderStateTest extends BaseOrderRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsInvalidIntegerFormat($order_id)
    {
        $this->assertSame(
            0,
            $this->repository->getCurrentOrderState($order_id)
        );
    }

    public function testWhenNoOrderFoundForGivenOrderId()
    {
        $order_id = 42;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            0,
            $this->repository->getCurrentOrderState($order_id)
        );
    }

    public function testWhenOrderAreFoundForGivenOrderId()
    {
        $order_id = 42;
        $order_state = 4242;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $order_state,
        ]);

        $this->assertSame(
            $order_state,
            $this->repository->getCurrentOrderState($order_id)
        );
    }
}
