<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group repository
 * @group operation_repository
 */
class bindPaymentIdTest extends BaseOperationRepository
{
    public function testUpdatesThePaymentIdOfAnExistingOperationRow()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_upc_operation' => '12',
            'operation_id' => 'op_1',
        ]);
        $this->repository->shouldReceive('updateEntity')->once()->with(12, ['payment_id' => 'pay_1'])->andReturn(true);
        $this->repository->shouldReceive('createEntity')->never();

        $this->assertTrue($this->repository->bindPaymentId('op_1', 'pay_1', 42));
    }

    public function testCreatesAPendingPlaceholderRowWhenTheOperationIsNotPersistedYet()
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
                    && 'pay_1' === $fields['payment_id']
                    && false === $fields['treated']
                    // Dated, or the purge could never select it.
                    && 1 === preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fields['date_add']);
            }))
            ->andReturn(7);
        $this->mockPurge();

        $this->assertTrue($this->repository->bindPaymentId('op_1', 'pay_1', 42));
    }

    /**
     * PRE-3627 review (L1): the frictionless payment's notification may save() the row between
     * getBy() and the insert, which then hits UNIQUE (operation_id).
     */
    public function testBindsOntoTheRowSavedConcurrentlyWhenThePlaceholderInsertFails()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->twice()->andReturn([], [
            'id_payplug_upc_operation' => '12',
            'operation_id' => 'op_1',
        ]);
        $this->repository->shouldReceive('createEntity')->once()->andThrow(new \Exception('Duplicate entry'));
        $this->repository->shouldReceive('updateEntity')->once()->with(12, ['payment_id' => 'pay_1'])->andReturn(true);
        $this->repository->shouldReceive('delete')->never();

        $this->assertTrue($this->repository->bindPaymentId('op_1', 'pay_1', 42));
    }

    public function testReturnsFalseWhenThePlaceholderInsertFailsAndNoRowExists()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->repository->shouldReceive('updateEntity')->never();

        $this->assertFalse($this->repository->bindPaymentId('op_1', 'pay_1', 42));
    }

    /**
     * PRE-3627 review: placeholders of payments that never got an order (refused frictionless
     * payment, abandoned 3DS challenge) would otherwise pile up forever.
     */
    public function testPurgesThePlaceholdersOlderThanADayWhenCreatingOne()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('createEntity')->once()->andReturn(7);
        $cutoff = date('Y-m-d H:i', time() - 86400);
        $this->repository->shouldReceive('where')->with('`order_id` LIKE "cart:%"')->once()->andReturn($this->repository);
        $this->repository->shouldReceive('where')->with('`outcome` = "three_ds_pending"')->once()->andReturn($this->repository);
        $this->repository->shouldReceive('where')->with(\Mockery::on(function ($restriction) use ($cutoff) {
            return 0 === strpos($restriction, '`date_add` < "' . $cutoff);
        }))->once()->andReturn($this->repository);
        $this->mockPurge();

        $this->assertTrue($this->repository->bindPaymentId('op_1', 'pay_1', 42));
    }

    public function testStillBindsWhenThePurgeFails()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('createEntity')->once()->andReturn(7);
        $this->repository->shouldReceive('getEntityObject')->andReturn($this->entity);
        $this->repository->shouldReceive('delete')->once()->andThrow(new \Exception('db down'));

        $this->assertTrue($this->repository->bindPaymentId('op_1', 'pay_1', 42));
    }

    public function testRejectsEmptyIdentifiers()
    {
        $this->repository->shouldReceive('getBy')->never();

        $this->assertFalse($this->repository->bindPaymentId('', 'pay_1', 42));
        $this->assertFalse($this->repository->bindPaymentId('op_1', '', 42));
    }

    private function mockPurge()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);
    }
}
