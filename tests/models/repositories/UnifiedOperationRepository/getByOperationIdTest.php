<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class getByOperationIdTest extends BaseUnifiedOperationRepository
{
    public function testReturnsOperationDataWhenFound()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn([
            'operation_id' => 'op_123',
            'exec_code' => '0000_SUCCESS',
            'outcome' => 'paid',
            'amount' => '1234',
            'order_id' => 'order_1',
        ]);

        $result = $this->repository->getByOperationId('op_123');

        $this->assertSame('op_123', $result->operationId);
        $this->assertSame('order_1', $result->orderId);
        $this->assertSame('0000_SUCCESS', $result->execCode);
        $this->assertSame('paid', $result->outcome);
        $this->assertSame(1234, $result->amount);
    }

    public function testThrowsWhenTheOperationDoesNotExist()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn(null);

        $this->expectException(PaymentNotFoundException::class);

        $this->repository->getByOperationId('op_123');
    }
}
