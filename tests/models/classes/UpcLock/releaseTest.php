<?php

namespace PayPlug\tests\models\classes\UpcLock;

/**
 * @group unit
 * @group unified
 */
class releaseTest extends BaseUpcLock
{
    public function testDelegatesToDeleteBy()
    {
        $this->upc_lock_repository->shouldReceive('deleteBy')->once()->with('lock_key', 'cart_1');

        $this->lock->release('cart_1');
        $this->addToAssertionCount(1);
    }
}
