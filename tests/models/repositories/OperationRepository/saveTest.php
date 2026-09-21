<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

use PayplugUnifiedCore\DataValues\OperationData;

/**
 * @group unit
 * @group repository
 * @group operation_repository
 */
class saveTest extends BaseOperationRepository
{
    public function testCreatesANewEntityWhenNoOperationExistsYet()
    {
        $operation_data = new OperationData('op_123', '0000_SUCCESS', 'paid', 1234, 'order_1');

        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn(null);
        $this->repository->shouldReceive('createEntity')->once()->with([
            'operation_id' => 'op_123',
            'order_id' => 'order_1',
            'exec_code' => '0000_SUCCESS',
            'outcome' => 'paid',
            'amount' => 1234,
            'treated' => false,
        ])->andReturn(1);

        $this->repository->save($operation_data);
        $this->addToAssertionCount(1);
    }

    public function testUpdatesTheExistingEntityWhenOneAlreadyExists()
    {
        $operation_data = new OperationData('op_123', '0000_SUCCESS', 'paid', 1234, 'order_1');

        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn([
            'id_payplug_upc_operation' => 7,
        ]);
        $this->repository->shouldReceive('updateEntity')->once()->with(7, [
            'operation_id' => 'op_123',
            'order_id' => 'order_1',
            'exec_code' => '0000_SUCCESS',
            'outcome' => 'paid',
            'amount' => 1234,
        ])->andReturn(true);
        $this->repository->shouldReceive('createEntity')->never();

        $this->repository->save($operation_data);
        $this->addToAssertionCount(1);
    }
}
