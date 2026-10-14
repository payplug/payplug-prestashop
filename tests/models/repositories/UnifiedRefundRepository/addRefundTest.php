<?php

namespace PayPlug\tests\models\repositories\UnifiedRefundRepository;

/**
 * @group unit
 * @group repository
 * @group unified_refund_repository
 */
class addRefundTest extends BaseUnifiedRefundRepository
{
    public function testInsertsAPendingRow()
    {
        $this->repository->shouldReceive('createEntity')
            ->once()
            ->withArgs(function ($fields) {
                return 'op_refund_1' === $fields['refund_operation_id']
                    && 'op_pay' === $fields['payment_operation_id']
                    && '42' === $fields['order_id']
                    && 1000 === $fields['amount']
                    && 'USD' === $fields['currency']
                    && 'pending' === $fields['status']
                    && 1 === preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fields['date_add']);
            })
            ->andReturn(7);

        $this->assertTrue($this->repository->addRefund('op_refund_1', 'op_pay', '42', 1000, 'USD'));
    }

    public function testRejectsANonPositiveAmount()
    {
        $this->repository->shouldReceive('createEntity')->never();

        $this->assertFalse($this->repository->addRefund('op_refund_1', 'op_pay', '42', 0, 'USD'));
    }

    public function testReturnsFalseWhenTheInsertFails()
    {
        $this->repository->shouldReceive('createEntity')->once()->andReturn(0);

        $this->assertFalse($this->repository->addRefund('op_refund_1', 'op_pay', '42', 1000, 'USD'));
    }
}
