<?php

namespace PayPlug\tests\models\repositories\OrderPaymentRepository;

/**
 * @group unit
 * @group repository
 * @group order_payment_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getAllByOrderTest extends BaseOrderPaymentRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsInvalidIntegerFormat($order_id)
    {
        $this->assertSame(
            [],
            $this->repository->getAllByOrder($order_id)
        );
    }

    public function testWhenNoOrderPaymentFoundForGivenOrderId()
    {
        $order_id = 42;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getAllByOrder($order_id)
        );
    }

    public function testWhenOrderPaymentAreFoundForGivenOrderId()
    {
        $order_id = 42;
        $order_payments = [
            [
                'id_order' => 1,
                'id_payment' => 'pay_azertyui',
            ],
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $order_payments,
        ]);

        $this->assertSame(
            $order_payments,
            $this->repository->getAllByOrder($order_id)
        );
    }
}
