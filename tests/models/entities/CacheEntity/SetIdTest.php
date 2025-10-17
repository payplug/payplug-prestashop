<?php

namespace PayPlug\tests\models\entities\CacheEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetIdTest extends BaseCacheEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->cache->setId(123);
    }

    public function testUpdateId()
    {
        $this->cache->setId(456);
        $this->assertSame(
            456,
            $this->cache->getId()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setId(123)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id
     */
    public function testSetIdInvalidType($id)
    {
        try {
            $this->cache->setId($id);
        } catch (BadParameterException $e) {
            $this->assertSame('Invalid argument, $id must be an integer', $e->getMessage());
        }
    }
}
