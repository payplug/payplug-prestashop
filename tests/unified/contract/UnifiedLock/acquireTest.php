<?php

namespace PayPlug\tests\unified\contract\UnifiedLock;

/**
 * @group unit
 * @group unified
 */
class acquireTest extends BaseUnifiedLock
{
    public function testSucceedsWhenTheInsertWins()
    {
        $this->unified_lock_repository->shouldReceive('createEntity')->once()->andReturn(1);

        $this->assertTrue($this->lock->acquire('cart_1', 30));
    }

    public function testRefusesWhenTheInsertLosesToAnUnexpiredLock()
    {
        $this->unified_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->unified_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(false);

        $this->assertFalse($this->lock->acquire('cart_1', 30));
    }

    public function testStealsAnExpiredLockAndReturnsTrue()
    {
        $this->unified_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->unified_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(true);

        $this->assertTrue($this->lock->acquire('cart_1', 30));
    }

    public function testRefusesWhenTheInsertLosesAndTheRowIsGoneByTheTimeItAttemptsTheSteal()
    {
        $this->unified_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->unified_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(false);

        $this->assertFalse($this->lock->acquire('cart_1', 30));
    }

    public function testOnlyOneOfTwoConcurrentStealersOnTheSameExpiredLockSucceeds()
    {
        // Both callers lose the INSERT (the row already exists, expired) and both attempt the
        // atomic steal; only the one whose UPDATE actually affects a row (simulated here via
        // UnifiedLockRepository::stealExpired()'s own return value, which is itself gated on
        // Db::Affected_Rows() === 1) wins.
        $this->unified_lock_repository->shouldReceive('createEntity')->twice()->andReturn(0);
        $this->unified_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(true);
        $this->unified_lock_repository->shouldReceive('stealExpired')
            ->once()
            ->with('cart_1', \Mockery::type('int'))
            ->andReturn(false);

        $winner = $this->lock->acquire('cart_1', 30);
        $loser = $this->lock->acquire('cart_1', 30);

        $this->assertTrue($winner);
        $this->assertFalse($loser);
    }
}
