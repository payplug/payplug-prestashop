<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class getRequestedAmountByOperationIdTest extends BaseUnifiedOperationRepository
{
    public function testReturnsTheAmountRequestedAtCreation()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['requested_amount' => '1234']);

        $this->assertSame(1234, $this->repository->getRequestedAmountByOperationId('op_1'));
    }

    public function testReturnsNullForARowRecordedWithoutRequestedAmount()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['requested_amount' => null]);

        $this->assertNull($this->repository->getRequestedAmountByOperationId('op_1'));
    }

    public function testReturnsNullWhenTheOperationIsUnknown()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);

        $this->assertNull($this->repository->getRequestedAmountByOperationId('op_1'));
    }
}
