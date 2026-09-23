<?php

namespace PayPlug\tests\models\classes\UpcLock;

/**
 * @group unit
 * @group unified
 */
class releaseTest extends BaseUpcLock
{
    public function testDeletesTheRowItAcquiredWhenStillIntact()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(1);
        $this->lock->acquire('cart_1', 30);

        $this->upc_lock_repository->shouldReceive('getBy')->with('lock_key', 'cart_1')->andReturn([
            'id_payplug_upc_lock' => 1,
            'lock_key' => 'cart_1',
            'expires_at' => time() + 30,
        ]);
        $this->upc_lock_repository->shouldReceive('deleteBy')->once()->with('lock_key', 'cart_1');

        $this->lock->release('cart_1');
        $this->addToAssertionCount(1);
    }

    public function testDoesNotDeleteARowStolenByAnotherOwnerSinceAcquiring()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(1);
        $this->lock->acquire('cart_1', 30);

        // A different expires_at than what acquire() above just set: someone else stole this key
        // after our TTL ran out, in between our acquire() and this release() call.
        $this->upc_lock_repository->shouldReceive('getBy')->with('lock_key', 'cart_1')->andReturn([
            'id_payplug_upc_lock' => 1,
            'lock_key' => 'cart_1',
            'expires_at' => time() + 999,
        ]);
        $this->upc_lock_repository->shouldReceive('deleteBy')->never();

        $this->lock->release('cart_1');
        $this->addToAssertionCount(1);
    }

    public function testIsANoOpWhenThisInstanceNeverAcquiredTheKey()
    {
        $this->upc_lock_repository->shouldReceive('getBy')->never();
        $this->upc_lock_repository->shouldReceive('deleteBy')->never();

        $this->lock->release('cart_1');
        $this->addToAssertionCount(1);
    }
}
