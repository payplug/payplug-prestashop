<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

/**
 * @group unit
 * @group repository
 * @group operation_repository
 */
class getPaymentIdByOperationIdTest extends BaseOperationRepository
{
    public function testReturnsTheRecordedPaymentId()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['payment_id' => 'pay_1']);

        $this->assertSame('pay_1', $this->repository->getPaymentIdByOperationId('op_1'));
    }

    public function testReturnsNullWhenNoPaymentIdWasRecorded()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['payment_id' => null]);

        $this->assertNull($this->repository->getPaymentIdByOperationId('op_1'));
    }

    public function testReturnsNullWhenTheOperationIsUnknown()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);

        $this->assertNull($this->repository->getPaymentIdByOperationId('op_1'));
    }
}
