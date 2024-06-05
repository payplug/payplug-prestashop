<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_state_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getOrderHistoryTest extends BaseOrderStateRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsInvalidIntegerFormat($order_id)
    {
        $lang_id = 42;
        $this->assertSame(
            [],
            $this->repository->getOrderHistory($order_id, $lang_id)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $lang_id
     */
    public function testWhenGivenLangIdIsInvalidIntegerFormat($lang_id)
    {
        $order_id = 42;
        $this->assertSame(
            [],
            $this->repository->getOrderHistory($order_id, $lang_id)
        );
    }

    public function testWhenNoHistoryFoundForGivenOrderId()
    {
        $order_id = 42;
        $lang_id = 1;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'leftJoin' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getOrderHistory($order_id, $lang_id)
        );
    }

    public function testWhenHistoryAreFoundForGivenOrderId()
    {
        $order_id = 42;
        $lang_id = 1;
        $histories = [
            [
                'id_order_state' => 1,
                'name' => 'order state 1',
            ],
            [
                'id_order_state' => 2,
                'name' => 'order state 2',
            ],
            [
                'id_order_state' => 3,
                'name' => 'order state 3',
            ],
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'leftJoin' => $this->repository,
            'where' => $this->repository,
            'build' => $histories,
        ]);

        $this->assertSame(
            $histories,
            $this->repository->getOrderHistory($order_id, $lang_id)
        );
    }
}
