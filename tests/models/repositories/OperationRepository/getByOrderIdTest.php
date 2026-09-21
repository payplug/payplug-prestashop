<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

use PayplugUnifiedCore\Exceptions\PaymentNotFoundException;

/**
 * @group unit
 * @group repository
 * @group operation_repository
 */
class getByOrderIdTest extends BaseOperationRepository
{
    public function testReturnsOperationDataWhenFound()
    {
        $this->repository->shouldReceive('getBy')->with('order_id', 'order_1')->andReturn([
            'operation_id' => 'op_123',
            'exec_code' => '0000_SUCCESS',
            'outcome' => 'paid',
            'amount' => '1234',
            'order_id' => 'order_1',
        ]);

        $result = $this->repository->getByOrderId('order_1');

        $this->assertSame('op_123', $result->operationId);
        $this->assertSame('order_1', $result->orderId);
        $this->assertSame('0000_SUCCESS', $result->execCode);
        $this->assertSame('paid', $result->outcome);
        $this->assertSame(1234, $result->amount);
    }

    public function testThrowsWhenNoOperationMatchesTheOrder()
    {
        $this->repository->shouldReceive('getBy')->with('order_id', 'order_1')->andReturn(null);

        $this->expectException(PaymentNotFoundException::class);

        $this->repository->getByOrderId('order_1');
    }
}
