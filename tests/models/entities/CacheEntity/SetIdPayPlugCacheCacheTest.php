<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetIdPayPlugCacheCacheTest extends BaseCacheEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->cache->setIdPayPlugCache('test_id');
    }

    public function testUpdateIdPayPlugCache()
    {
        $this->cache->setIdPayPlugCache('updated_id');
        $this->assertSame(
            'updated_id',
            $this->cache->getIdPayPlugCache()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setIdPayPlugCache('test_id')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $id_payplug_cache
     */
    public function testSetIdPayPlugCacheInvalidType($id_payplug_cache)
    {
        try {
            $this->cache->setIdPayPlugCache($id_payplug_cache);
        } catch (BadParameterException $e) {
            $this->assertSame('Invalid argument, $id_payplug_cache must be a string', $e->getMessage());
        }
    }
}
