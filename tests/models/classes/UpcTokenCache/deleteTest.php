<?php

namespace PayPlug\tests\models\classes\UpcTokenCache;

/**
 * @group unit
 * @group unified
 */
class deleteTest extends BaseUpcTokenCache
{
    public function testDeletesThePrefixedRow()
    {
        $this->cache_repository->shouldReceive('deleteBy')->once()->with('cache_key', 'upc_token:uhf_cart_token:42')->andReturn(true);

        $this->cache->delete('uhf_cart_token:42');
        $this->addToAssertionCount(1);
    }

    public function testDoesNotFailWhenNothingWasDeleted()
    {
        $this->cache_repository->shouldReceive('deleteBy')->once()->with('cache_key', 'upc_token:foo')->andReturn(false);

        $this->cache->delete('foo');
        $this->addToAssertionCount(1);
    }
}
