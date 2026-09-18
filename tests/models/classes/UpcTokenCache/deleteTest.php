<?php

namespace PayPlug\tests\models\classes\UpcTokenCache;

/**
 * @group unit
 * @group unified
 */
class deleteTest extends BaseUpcTokenCache
{
    public function testDelegatesToDeleteBy()
    {
        $this->cache_repository->shouldReceive('deleteBy')->once()->with('cache_key', 'upc_token:foo');

        $this->cache->delete('foo');
        $this->addToAssertionCount(1);
    }
}
