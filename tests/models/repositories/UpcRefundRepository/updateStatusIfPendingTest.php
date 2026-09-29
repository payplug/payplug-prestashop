<?php

namespace PayPlug\tests\models\repositories\UpcRefundRepository;

/**
 * @group unit
 * @group repository
 * @group upc_refund_repository
 */
class updateStatusIfPendingTest extends BaseUpcRefundRepository
{
    public function testReturnsTrueWhenExactlyOnePendingRowChanged()
    {
        $this->mockAffectedRows(1);
        $this->repository->shouldReceive('where')->with('refund_operation_id = "op_refund_1"')->once()->andReturn($this->repository);
        $this->repository->shouldReceive('where')->with('status = "pending"')->once()->andReturn($this->repository);

        $this->assertTrue($this->repository->updateStatusIfPending('op_refund_1', 'confirmed'));
    }

    public function testReturnsFalseWhenTheRowWasNoLongerPending()
    {
        $this->mockAffectedRows(0);
        $this->repository->shouldReceive('where')->andReturn($this->repository);

        $this->assertFalse($this->repository->updateStatusIfPending('op_refund_1', 'failed'));
    }

    public function testRejectsAnUnknownStatus()
    {
        $this->repository->shouldReceive('update')->never();

        $this->assertFalse($this->repository->updateStatusIfPending('op_refund_1', 'pending'));
        $this->assertFalse($this->repository->updateStatusIfPending('op_refund_1', 'whatever'));
    }

    private function mockAffectedRows(int $rows)
    {
        $query_adapter = \Mockery::mock('QueryAdapter');
        $plugin = \Mockery::mock('Plugin');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getQueryAdapter')->andReturn($query_adapter);
        $query_adapter->shouldReceive('getAffectedRows')->once()->andReturn($rows);

        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'update' => $this->repository,
            'table' => $this->repository,
            'set' => $this->repository,
            'build' => true,
        ]);
    }
}
