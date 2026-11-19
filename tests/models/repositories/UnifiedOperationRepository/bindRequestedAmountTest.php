<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class bindRequestedAmountTest extends BaseUnifiedOperationRepository
{
    public function testUpdatesTheRequestedAmountOfAnExistingOperationRow()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
        ]);
        $this->repository->shouldReceive('updateEntity')->once()->with(12, ['requested_amount' => 1234])->andReturn(true);
        $this->repository->shouldReceive('createEntity')->never();

        $this->assertTrue($this->repository->bindRequestedAmount('op_1', 1234, 42));
    }

    public function testCreatesAPendingPlaceholderRowWithoutPaymentIdWhenTheOperationIsNotPersistedYet()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('createEntity')
            ->once()
            ->with(\Mockery::on(function (array $fields) {
                return 'op_1' === $fields['operation_id']
                    && 'cart:42' === $fields['order_id']
                    && '0001' === $fields['exec_code']
                    && PaymentOutcome::THREE_DS_PENDING === $fields['outcome']
                    && 0 === $fields['amount']
                    && 1234 === $fields['requested_amount']
                    && false === $fields['treated']
                    && !array_key_exists('payment_id', $fields)
                    && 1 === preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fields['date_add']);
            }))
            ->andReturn(7);
        $this->mockPurge();

        $this->assertTrue($this->repository->bindRequestedAmount('op_1', 1234, 42));
    }

    public function testBindsOntoTheRowSavedConcurrentlyWhenThePlaceholderInsertFails()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->twice()->andReturn([], [
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
        ]);
        $this->repository->shouldReceive('createEntity')->once()->andThrow(new \Exception('Duplicate entry'));
        $this->repository->shouldReceive('updateEntity')->once()->with(12, ['requested_amount' => 1234])->andReturn(true);
        $this->repository->shouldReceive('delete')->never();

        $this->assertTrue($this->repository->bindRequestedAmount('op_1', 1234, 42));
    }

    public function testReturnsFalseWhenThePlaceholderInsertFailsAndNoRowExists()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->repository->shouldReceive('updateEntity')->never();

        $this->assertFalse($this->repository->bindRequestedAmount('op_1', 1234, 42));
    }

    public function testRejectsAnEmptyOperationId()
    {
        $this->repository->shouldReceive('getBy')->never();
        $this->repository->shouldReceive('createEntity')->never();

        $this->assertFalse($this->repository->bindRequestedAmount('', 1234, 42));
    }
}
