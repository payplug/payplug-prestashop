<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class bindPaymentIdIfMissingTest extends BaseUnifiedOperationRepository
{
    public function testWritesThePaymentIdOfARowThatHasNone()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
            'payment_id' => null,
        ]);
        $this->repository->shouldReceive('createEntity')->never();
        $this->repository->shouldReceive('updateEntity')->never();
        $this->mockConditionalUpdate(1);

        $this->assertTrue($this->repository->bindPaymentIdIfMissing('op_1', 'pay_1', 42));
    }

    public function testLeavesARecordedPaymentIdUntouched()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
            'payment_id' => 'pay_recorded',
        ]);
        $this->repository->shouldReceive('createEntity')->never();
        $this->repository->shouldReceive('updateEntity')->never();
        $this->mockConditionalUpdate(0);

        $this->assertFalse($this->repository->bindPaymentIdIfMissing('op_1', 'pay_1', 42));
    }

    public function testCreatesAPendingPlaceholderRowWhenTheOperationIsNotPersistedYet()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('createEntity')
            ->once()
            ->with(\Mockery::on(function (array $fields) {
                return 'op_1' === $fields['operation_id']
                    && 'pay_1' === $fields['payment_id']
                    && 'cart:42' === $fields['order_id']
                    && '0001' === $fields['exec_code']
                    && PaymentOutcome::THREE_DS_PENDING === $fields['outcome']
                    && 0 === $fields['amount']
                    && false === $fields['treated']
                    && !array_key_exists('requested_amount', $fields);
            }))
            ->andReturn(7);
        $this->repository->shouldReceive('update')->never();
        $this->mockPurge();

        $this->assertTrue($this->repository->bindPaymentIdIfMissing('op_1', 'pay_1', 42));
    }

    public function testOnlyFillsAnEmptyPaymentIdOfTheRowSavedConcurrentlyWhenThePlaceholderInsertFails()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('createEntity')->once()->andThrow(new \Exception('Duplicate entry'));
        $this->repository->shouldReceive('updateEntity')->never();
        $this->repository->shouldReceive('delete')->never();
        $this->mockConditionalUpdate(0);

        $this->assertFalse($this->repository->bindPaymentIdIfMissing('op_1', 'pay_1', 42));
    }

    /**
     * @dataProvider emptyIdsProvider
     *
     * @param string $operation_id
     * @param string $payment_id
     */
    public function testRejectsAnEmptyId($operation_id, $payment_id)
    {
        $this->repository->shouldReceive('getBy')->never();
        $this->repository->shouldReceive('createEntity')->never();
        $this->repository->shouldReceive('update')->never();

        $this->assertFalse($this->repository->bindPaymentIdIfMissing($operation_id, $payment_id, 42));
    }

    public function emptyIdsProvider()
    {
        return [
            'empty operation id' => ['', 'pay_1'],
            'empty payment id' => ['op_1', ''],
        ];
    }

    /**
     * @param int $affected_rows
     */
    private function mockConditionalUpdate($affected_rows)
    {
        $query_adapter = \Mockery::mock('QueryAdapter');
        $plugin = \Mockery::mock('Plugin');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getQueryAdapter')->andReturn($query_adapter);
        $query_adapter->shouldReceive('getAffectedRows')->once()->andReturn($affected_rows);

        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'update' => $this->repository,
            'table' => $this->repository,
            'build' => true,
        ]);
        $this->repository->shouldReceive('set')->once()->with('`payment_id` = "pay_1"')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`operation_id` = "op_1"')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()
            ->with('`payment_id` IS NULL OR `payment_id` = ""')
            ->andReturn($this->repository);
    }
}
