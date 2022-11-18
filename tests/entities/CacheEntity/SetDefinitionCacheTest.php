<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class SetDefinitionCacheTest extends TestCase
{
    protected $cache;
    protected $definition;
    protected $definition_alt;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->definition = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->definition_alt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->cache->setDefinition($this->definition);
    }

    public function testUpdateDefinition()
    {
        $this->cache->setDefinition($this->definition_alt);
        $this->assertSame(
            $this->definition_alt,
            $this->cache->getDefinition()
        );
    }

    public function testReturnCacheEntity()
    {
        $this->assertInstanceOf(
            CacheEntity::class,
            $this->cache->setDefinition($this->definition_alt)
        );
    }

    /**
     * @group entity_exception
     * @group cache_exception
     * @group cache_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAnArray()
    {
        $this->expectException(BadParameterException::class);
        $this->cache->setDefinition(42);
    }
}
