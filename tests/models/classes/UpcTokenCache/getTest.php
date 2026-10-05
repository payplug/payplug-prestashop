<?php

namespace PayPlug\tests\models\classes\UpcTokenCache;

/**
 * @group unit
 * @group unified
 */
class getTest extends BaseUpcTokenCache
{
    public function testReturnsTheDecodedValueWhenNotExpired()
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([
            'id_payplug_cache' => 7,
            'cache_key' => 'upc_token:foo',
            'cache_value' => json_encode(['value' => base64_encode('<form>3DS</form>'), 'expires_at' => time() + 60]),
        ]);
        $this->cache_repository->shouldReceive('deleteBy')->never();

        $this->assertSame('<form>3DS</form>', $this->cache->get('foo'));
    }

    /**
     * @dataProvider missingRowProvider
     *
     * @param mixed $row
     */
    public function testReturnsNullWhenNoRowExists($row)
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn($row);

        $this->assertNull($this->cache->get('foo'));
    }

    public function missingRowProvider()
    {
        yield 'empty row' => [[]];

        yield 'null' => [null];

        yield 'false' => [false];
    }

    public function testDeletesTheRowAndReturnsNullWhenExpired()
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([
            'id_payplug_cache' => 7,
            'cache_key' => 'upc_token:foo',
            'cache_value' => json_encode(['value' => base64_encode('a-jwt'), 'expires_at' => time() - 1]),
        ]);
        $this->cache_repository->shouldReceive('deleteBy')->once()->with('cache_key', 'upc_token:foo');

        $this->assertNull($this->cache->get('foo'));
    }

    /**
     * @dataProvider malformedValueProvider
     *
     * @param string $cache_value
     */
    public function testReturnsNullWhenTheStoredValueIsMalformed($cache_value)
    {
        $this->cache_repository->shouldReceive('getBy')->with('cache_key', 'upc_token:foo')->andReturn([
            'id_payplug_cache' => 7,
            'cache_key' => 'upc_token:foo',
            'cache_value' => $cache_value,
        ]);
        $this->cache_repository->shouldReceive('deleteBy')->never();

        $this->assertNull($this->cache->get('foo'));
    }

    public function malformedValueProvider()
    {
        yield 'not json' => ['not json'];

        yield 'json scalar' => ['"a-jwt"'];

        yield 'missing value' => [json_encode(['expires_at' => time() + 60])];

        yield 'missing expiry' => [json_encode(['value' => base64_encode('a-jwt')])];

        yield 'not base64' => [json_encode(['value' => '***', 'expires_at' => time() + 60])];
    }
}
