<?php

namespace PayPlug\tests\models\repositories\UpcLockRepository;

/**
 * @group unit
 * @group repository
 * @group upc_lock_repository
 */
class stealExpiredTest extends BaseUpcLockRepository
{
    public function testReturnsFalseWhenLockKeyIsInvalid()
    {
        $this->assertFalse($this->repository->stealExpired('', time() + 30));
        $this->assertFalse($this->repository->stealExpired(null, time() + 30));
    }

    public function testReturnsFalseWhenNewExpiresAtIsInvalid()
    {
        $this->assertFalse($this->repository->stealExpired('cart_1', 0));
        $this->assertFalse($this->repository->stealExpired('cart_1', 'not-an-int'));
    }

    public function testReturnsTrueWhenTheUpdateAffectsExactlyOneRow()
    {
        $query_adapter = \Mockery::mock('QueryAdapter');
        $plugin = \Mockery::mock('Plugin');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getQueryAdapter')->andReturn($query_adapter);
        $query_adapter->shouldReceive('getAffectedRows')->once()->andReturn(1);

        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'update' => $this->repository,
            'table' => $this->repository,
            'set' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertTrue($this->repository->stealExpired('cart_1', time() + 30));
    }

    public function testReturnsFalseWhenTheUpdateAffectsZeroRows()
    {
        // Simulates the loser of a concurrent steal race, or a lock_key that's expired
        // but already been stolen/renewed by someone else - either way, this caller's
        // own UPDATE matched no row.
        $query_adapter = \Mockery::mock('QueryAdapter');
        $plugin = \Mockery::mock('Plugin');
        $this->dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getQueryAdapter')->andReturn($query_adapter);
        $query_adapter->shouldReceive('getAffectedRows')->once()->andReturn(0);

        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'update' => $this->repository,
            'table' => $this->repository,
            'set' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertFalse($this->repository->stealExpired('cart_1', time() + 30));
    }

    public function testReturnsFalseWhenEntityObjectCannotBeResolved()
    {
        $this->repository->shouldReceive('getEntityObject')->andReturn(null);

        $this->assertFalse($this->repository->stealExpired('cart_1', time() + 30));
    }
}
