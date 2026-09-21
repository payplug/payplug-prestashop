<?php

namespace PayPlug\tests\models\repositories\OperationRepository;

/**
 * @group unit
 * @group repository
 * @group operation_repository
 */
class isTreatedTest extends BaseOperationRepository
{
    public function testReturnsTrueWhenTheOperationIsTreated()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn(['treated' => true]);

        $this->assertTrue($this->repository->isTreated('op_123'));
    }

    public function testReturnsFalseWhenTheOperationIsNotTreated()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn(['treated' => false]);

        $this->assertFalse($this->repository->isTreated('op_123'));
    }

    public function testReturnsFalseWhenTheOperationDoesNotExist()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_123')->andReturn(null);

        $this->assertFalse($this->repository->isTreated('op_123'));
    }
}
