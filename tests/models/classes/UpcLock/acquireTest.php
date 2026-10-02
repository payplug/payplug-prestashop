<?php

namespace PayPlug\tests\models\classes\UpcLock;

/**
 * @group unit
 * @group unified
 */
class acquireTest extends BaseUpcLock
{
    public function testSucceedsWhenTheInsertWins()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(1);

        $this->assertTrue($this->lock->acquire('cart_1', 30));
    }

    public function testRefusesWhenTheInsertLosesToAnUnexpiredLock()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->upc_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(false);

        $this->assertFalse($this->lock->acquire('cart_1', 30));
    }

    public function testStealsAnExpiredLockAndReturnsTrue()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->upc_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(true);

        $this->assertTrue($this->lock->acquire('cart_1', 30));
    }

    public function testRefusesWhenTheInsertLosesAndTheRowIsGoneByTheTimeItAttemptsTheSteal()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->upc_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(false);

        $this->assertFalse($this->lock->acquire('cart_1', 30));
    }

    public function testOnlyOneOfTwoConcurrentStealersOnTheSameExpiredLockSucceeds()
    {
        // Both callers lose the INSERT (the row already exists, expired) and both attempt the
        // atomic steal; only the one whose UPDATE actually affects a row (simulated here via
        // UpcLockRepository::stealExpired()'s own return value, which is itself gated on
        // Db::Affected_Rows() === 1) wins.
        $this->upc_lock_repository->shouldReceive('createEntity')->twice()->andReturn(0);
        $this->upc_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(true);
        $this->upc_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(false);

        $winner = $this->lock->acquire('cart_1', 30);
        $loser = $this->lock->acquire('cart_1', 30);

        $this->assertTrue($winner);
        $this->assertFalse($loser);
    }
}
