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
        $this->upc_lock_repository->shouldReceive('getBy')->with('lock_key', 'cart_1')->andReturn([
            'id_payplug_upc_lock' => 1,
            'lock_key' => 'cart_1',
            'expires_at' => time() + 30,
        ]);

        $this->assertFalse($this->lock->acquire('cart_1', 30));
    }

    public function testStealsAnExpiredLockAndReturnsTrue()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->upc_lock_repository->shouldReceive('getBy')->with('lock_key', 'cart_1')->andReturn([
            'id_payplug_upc_lock' => 1,
            'lock_key' => 'cart_1',
            'expires_at' => time() - 30,
        ]);
        $this->upc_lock_repository->shouldReceive('updateEntity')->once()->with(1, \Mockery::type('array'))->andReturn(true);

        $this->assertTrue($this->lock->acquire('cart_1', 30));
    }

    public function testRefusesWhenTheInsertLosesAndTheRowIsGoneByTheTimeItRereads()
    {
        $this->upc_lock_repository->shouldReceive('createEntity')->once()->andReturn(0);
        $this->upc_lock_repository->shouldReceive('getBy')->with('lock_key', 'cart_1')->andReturn([]);

        $this->assertFalse($this->lock->acquire('cart_1', 30));
    }
}
