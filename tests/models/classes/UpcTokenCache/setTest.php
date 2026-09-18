<?php

namespace PayPlug\tests\models\classes\UpcTokenCache;

/**
 * @group unit
 * @group unified
 */
class setTest extends BaseUpcTokenCache
{
    public function testCreatesANewRowWhenNoneExists()
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([]);
        $this->cache_repository->shouldReceive('createEntity')->once()->withArgs(function ($fields) {
            $decoded = json_decode($fields['cache_value'], true);

            return 'upc_token:foo' === $fields['cache_key'] && 'a-jwt' === $decoded['value'];
        });

        $this->cache->set('foo', 'a-jwt', 60);
        $this->addToAssertionCount(1);
    }

    public function testUpdatesTheExistingRowWhenOneExists()
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([
            'id_payplug_cache' => 7,
            'cache_key' => 'upc_token:foo',
            'cache_value' => json_encode(['value' => 'old-jwt', 'expires_at' => time() + 10]),
        ]);
        $this->cache_repository->shouldReceive('updateEntity')->once()->withArgs(function ($id, $fields) {
            $decoded = json_decode($fields['cache_value'], true);

            return 7 === $id && 'new-jwt' === $decoded['value'];
        });

        $this->cache->set('foo', 'new-jwt', 60);
        $this->addToAssertionCount(1);
    }
}
