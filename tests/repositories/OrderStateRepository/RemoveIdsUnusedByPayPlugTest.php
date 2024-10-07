<?php

namespace PayPlug\tests\repositories\OrderStateRepository;

use PayPlug\tests\mock\OrderStateMock;

/**
 * @group unit
 * @group old_repository
 * @group order_state
 * @group order_state_repository
 *
 * @runTestsInSeparateProcesses
 */
final class RemoveIdsUnusedByPayPlugTest extends BaseOrderStateRepository
{
    public function setUp()
    {
        parent::setUp();

        $this->orderStateMock = OrderStateMock::get();
    }

    public function testWithEmptyPayplugOrderStateIdList()
    {
        $this->order_state_repository->shouldReceive([
            'getIdsByModuleName' => [],
            'getIdsUsedByPayPlug' => [],
        ]);

        $this->repo->shouldReceive([
            'isUsedByOrders' => [],
        ]);

        $this->assertSame(
            true,
            $this->repo->removeIdsUnusedByPayPlug()
        );
    }

    public function testWithEmptyUsedList()
    {
        $this->order_state_repository->shouldReceive([
            'getIdsByModuleName' => [
                [
                    'id_order_state' => 1,
                ],
                [
                    'id_order_state' => 2,
                ],
                [
                    'id_order_state' => 3,
                ],
                [
                    'id_order_state' => 4,
                ],
            ],
            'getIdsUsedByPayPlug' => [],
        ]);

        $this->repo->shouldReceive([
            'isUsedByOrders' => [],
        ]);

        $order_state = \Mockery::mock('OrderState');
        $order_state->shouldReceive([
            'softDelete' => true,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $order_state,
        ]);

        $this->assertSame(
            true,
            $this->repo->removeIdsUnusedByPayPlug()
        );
    }

    public function testWithNoEmptyUsedList()
    {
        $this->order_state_repository->shouldReceive([
            'getIdsByModuleName' => [
                [
                    'id_order_state' => 1,
                ],
                [
                    'id_order_state' => 2,
                ],
                [
                    'id_order_state' => 3,
                ],
                [
                    'id_order_state' => 4,
                ],
            ],
            'getIdsUsedByPayPlug' => [
                [
                    'value' => 5,
                ],
                [
                    'value' => 6,
                ],
                [
                    'value' => 7,
                ],
                [
                    'value' => 8,
                ],
            ],
        ]);

        $this->repo->shouldReceive([
            'isUsedByOrders' => [
                5,
                6,
                7,
                8,
            ],
        ]);

        $order_state = \Mockery::mock('OrderState');
        $order_state->shouldReceive([
            'softDelete' => true,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $order_state,
        ]);

        $this->assertSame(
            true,
            $this->repo->removeIdsUnusedByPayPlug()
        );
    }

    public function testWithFailedDelete()
    {
        $this->order_state_repository->shouldReceive([
            'getIdsByModuleName' => [
                [
                    'id_order_state' => 1,
                ],
                [
                    'id_order_state' => 2,
                ],
                [
                    'id_order_state' => 3,
                ],
                [
                    'id_order_state' => 4,
                ],
            ],
            'getIdsUsedByPayPlug' => [
                [
                    'value' => 1,
                ],
                [
                    'value' => 6,
                ],
                [
                    'value' => 7,
                ],
                [
                    'value' => 8,
                ],
            ],
        ]);

        $this->repo->shouldReceive([
            'isUsedByOrders' => [
                1,
                6,
                7,
                8,
            ],
        ]);

        $order_state = \Mockery::mock('OrderState');
        $order_state->shouldReceive([
            'softDelete' => false,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $order_state,
        ]);

        $this->assertSame(
            false,
            $this->repo->removeIdsUnusedByPayPlug()
        );
    }
}
