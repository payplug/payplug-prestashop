<?php

namespace PayPlug\tests\unified\contract\UnifiedLock;

/**
 * @group unit
 * @group unified
 */
class releaseTest extends BaseUnifiedLock
{
    public function testDeletesViaTheOwnedExpiresAtItAcquired()
    {
        $this->unified_lock_repository->shouldReceive('createEntity')->once()->andReturn(1);
        $this->lock->acquire('cart_1', 30);

        $this->unified_lock_repository->shouldReceive('deleteOwned')
            ->once()
            ->withArgs(function ($key, $expires_at) {
                return 'cart_1' === $key && $expires_at > time();
            });

        $this->lock->release('cart_1');
        $this->addToAssertionCount(1);
    }

    public function testIsANoOpWhenThisInstanceNeverAcquiredTheKey()
    {
        $this->unified_lock_repository->shouldReceive('deleteOwned')->never();

        $this->lock->release('cart_1');
        $this->addToAssertionCount(1);
    }
}
