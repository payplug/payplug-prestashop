<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

/**
 * @group unit
 * @group repository
 * @group operation_repository
 */
class markTreatedTest extends BaseOperationRepository
{
    public function testUpdatesTreatedWhenTheOperationExists()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn([
            'id_payplug_upc_operation' => 7,
        ]);
        $this->repository->shouldReceive('updateEntity')->once()->with(7, ['treated' => true])->andReturn(true);

        $this->repository->markTreated('op_123');
        $this->addToAssertionCount(1);
    }

    public function testDoesNothingWhenTheOperationDoesNotExist()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn(null);
        $this->repository->shouldReceive('updateEntity')->never();

        $this->repository->markTreated('op_123');
        $this->addToAssertionCount(1);
    }
}
