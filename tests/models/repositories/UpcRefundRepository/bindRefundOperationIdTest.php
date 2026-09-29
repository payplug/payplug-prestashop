<?php

namespace PayPlug\tests\models\repositories\UpcRefundRepository;

/**
 * @group unit
 * @group repository
 * @group upc_refund_repository
 */
class bindRefundOperationIdTest extends BaseUpcRefundRepository
{
    public function testReplacesThePlaceholderIdByTheRefundOperationId()
    {
        // Before mockAffectedRows(): its catch-all set() would match first otherwise.
        $this->repository->shouldReceive('set')->with('refund_operation_id = "op_refund_1"')->once()->andReturn($this->repository);
        $this->repository->shouldReceive('set')->with(\Mockery::pattern('/^date_upd = /'))->once()->andReturn($this->repository);
        $this->repository->shouldReceive('where')->with('refund_operation_id = "intent:abc"')->once()->andReturn($this->repository);
        $this->mockAffectedRows(1);

        $this->assertTrue($this->repository->bindRefundOperationId('intent:abc', 'op_refund_1'));
    }

    public function testReturnsFalseWhenNoRowCarriesThePlaceholderId()
    {
        $this->mockAffectedRows(0);
        $this->repository->shouldReceive('where')->andReturn($this->repository);

        $this->assertFalse($this->repository->bindRefundOperationId('intent:abc', 'op_refund_1'));
    }

    public function testRejectsEmptyIdentifiers()
    {
        $this->repository->shouldReceive('update')->never();

        $this->assertFalse($this->repository->bindRefundOperationId('', 'op_refund_1'));
        $this->assertFalse($this->repository->bindRefundOperationId('intent:abc', ''));
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
