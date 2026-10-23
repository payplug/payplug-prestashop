<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class markTreatedTest extends BaseUnifiedOperationRepository
{
    public function testFlagsTheExistingRowAsTreated()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
            'treated' => '0',
        ]);
        $this->repository->shouldReceive('updateEntity')->once()->with(12, ['treated' => true])->andReturn(true);

        $this->repository->markTreated('op_1');
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider unknownOperationProvider
     *
     * @param mixed $row
     */
    public function testDoesNothingWhenTheOperationIsUnknown($row)
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn($row);
        $this->repository->shouldReceive('updateEntity')->never();
        $this->repository->shouldReceive('createEntity')->never();

        $this->repository->markTreated('op_1');
        $this->addToAssertionCount(1);
    }

    public function unknownOperationProvider()
    {
        yield 'empty row' => [[]];

        yield 'null' => [null];

        yield 'false' => [false];
    }
}
