<?php

namespace PayPlug\tests\models\repositories\UnifiedRefundRepository;

/**
 * @group unit
 * @group repository
 * @group unified_refund_repository
 */
class getByRefundOperationIdTest extends BaseUnifiedRefundRepository
{
    public function testReturnsTheRowWhenFound()
    {
        $row = ['refund_operation_id' => 'op_refund_1', 'amount' => '1000', 'status' => 'pending'];
        $this->repository->shouldReceive('getBy')->with('refund_operation_id', 'op_refund_1')->andReturn($row);

        $this->assertSame($row, $this->repository->getByRefundOperationId('op_refund_1'));
    }

    public function testReturnsNullWhenNotFound()
    {
        $this->repository->shouldReceive('getBy')->with('refund_operation_id', 'op_unknown')->andReturn([]);

        $this->assertNull($this->repository->getByRefundOperationId('op_unknown'));
    }

    public function testReturnsNullForAnEmptyId()
    {
        $this->repository->shouldReceive('getBy')->never();

        $this->assertNull($this->repository->getByRefundOperationId(''));
    }
}
