<?php

namespace PayPlug\tests\models\classes\UpcTokenCache;

/**
 * @group unit
 * @group unified
 */
class getTest extends BaseUpcTokenCache
{
    public function testReturnsNullWhenNoRowExists()
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([]);

        $this->assertNull($this->cache->get('foo'));
    }

    public function testReturnsTheStoredValueWhenNotExpired()
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([
            'id_payplug_cache' => 1,
            'cache_key' => 'upc_token:foo',
            'cache_value' => json_encode(['value' => 'a-jwt', 'expires_at' => time() + 60]),
        ]);

        $this->assertSame('a-jwt', $this->cache->get('foo'));
    }

    public function testReturnsNullAndDeletesWhenExpired()
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([
            'id_payplug_cache' => 1,
            'cache_key' => 'upc_token:foo',
            'cache_value' => json_encode(['value' => 'a-jwt', 'expires_at' => time() - 60]),
        ]);
        $this->cache_repository->shouldReceive('deleteBy')->once()->with('cache_key', 'upc_token:foo');

        $this->assertNull($this->cache->get('foo'));
    }
}
