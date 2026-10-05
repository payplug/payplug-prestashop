<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group repository
 * @group operation_repository
 */
class getPaidByOrderIdTest extends BaseOperationRepository
{
    public function testReturnsThePaidOperationWhenTheOrderAlsoCarriesAFailedOne()
    {
        $this->repository->shouldReceive('getAllBy')->with('order_id', '99')->andReturn([
            ['operation_id' => 'op_failed', 'exec_code' => '9999', 'outcome' => PaymentOutcome::FAILED, 'amount' => '2900', 'order_id' => '99'],
            ['operation_id' => 'op_paid', 'exec_code' => '0000', 'outcome' => PaymentOutcome::PAID, 'amount' => '2900', 'order_id' => '99'],
        ]);

        $result = $this->repository->getPaidByOrderId('99');

        $this->assertSame('op_paid', $result->operationId);
        $this->assertSame(2900, $result->amount);
    }

    public function testReturnsNullWhenNoOperationIsPaid()
    {
        $this->repository->shouldReceive('getAllBy')->with('order_id', '99')->andReturn([
            ['operation_id' => 'op_pending', 'exec_code' => '0001', 'outcome' => PaymentOutcome::THREE_DS_PENDING, 'amount' => '2900', 'order_id' => '99'],
        ]);

        $this->assertNull($this->repository->getPaidByOrderId('99'));
    }

    public function testReturnsNullWhenTheOrderHasNoOperation()
    {
        $this->repository->shouldReceive('getAllBy')->with('order_id', '99')->andReturn([]);

        $this->assertNull($this->repository->getPaidByOrderId('99'));
    }
}
