<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class saveTest extends BaseUnifiedOperationRepository
{
    /**
     * @dataProvider unknownOperationProvider
     *
     * @param mixed $row
     */
    public function testInsertsAnUntreatedRowWhenTheOperationIsUnknown($row)
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn($row);
        $this->repository->shouldReceive('createEntity')
            ->once()
            ->with([
                'operation_id' => 'op_1',
                'order_id' => '42',
                'exec_code' => '0000',
                'outcome' => PaymentOutcome::PAID,
                'amount' => 1789,
                'treated' => false,
            ])
            ->andReturn(7);
        $this->repository->shouldReceive('updateEntity')->never();

        $this->repository->save(new OperationData('op_1', '0000', PaymentOutcome::PAID, 1789, '42'));
        $this->addToAssertionCount(1);
    }

    public function unknownOperationProvider()
    {
        yield 'empty row' => [[]];

        yield 'null' => [null];

        yield 'false' => [false];
    }

    public function testUpdatesTheExistingRowWithoutResettingTreatedNorPaymentId()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
            'order_id' => '42',
            'exec_code' => '0000',
            'outcome' => PaymentOutcome::PAID,
            'amount' => '1789',
            'treated' => '1',
            'payment_id' => 'pay_1',
        ]);
        $this->repository->shouldReceive('updateEntity')
            ->once()
            ->with(12, [
                'operation_id' => 'op_1',
                'order_id' => '42',
                'exec_code' => '0000',
                'outcome' => PaymentOutcome::PAID,
                'amount' => 1789,
            ])
            ->andReturn(true);
        $this->repository->shouldReceive('createEntity')->never();

        $this->repository->save(new OperationData('op_1', '0000', PaymentOutcome::PAID, 1789, '42'));
        $this->addToAssertionCount(1);
    }

    public function testCompletesThePlaceholderBoundBeforeTheOrderExisted()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
            'order_id' => 'cart:42',
            'exec_code' => '0001',
            'outcome' => PaymentOutcome::THREE_DS_PENDING,
            'amount' => '0',
            'treated' => '0',
            'payment_id' => 'pay_1',
        ]);
        $this->repository->shouldReceive('updateEntity')
            ->once()
            ->with(12, [
                'operation_id' => 'op_1',
                'order_id' => '1001',
                'exec_code' => '0000',
                'outcome' => PaymentOutcome::PAID,
                'amount' => 1789,
            ])
            ->andReturn(true);
        $this->repository->shouldReceive('createEntity')->never();

        $this->repository->save(new OperationData('op_1', '0000', PaymentOutcome::PAID, 1789, '1001'));
        $this->addToAssertionCount(1);
    }

    public function testKeepsTheRequestedAmountRecordedAtCreation()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
            'order_id' => 'cart:42',
            'exec_code' => '0001',
            'outcome' => PaymentOutcome::THREE_DS_PENDING,
            'amount' => '0',
            'treated' => '0',
            'payment_id' => 'pay_1',
            'requested_amount' => '1789',
        ]);
        $this->repository->shouldReceive('updateEntity')
            ->once()
            ->with(12, \Mockery::on(function (array $fields) {
                return !array_key_exists('requested_amount', $fields);
            }))
            ->andReturn(true);
        $this->repository->shouldReceive('createEntity')->never();

        $this->repository->save(new OperationData('op_1', '0000', PaymentOutcome::PAID, 1789, '1001'));
        $this->addToAssertionCount(1);
    }
}
