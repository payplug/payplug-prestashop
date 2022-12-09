<?php

use PayPlug\src\models\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetDefinitionCacheTest extends TestCase
{
    protected $cache;
    protected $definition;

    protected function setUp()
    {
        $this->cache = new CacheEntity();
        $this->definition = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->cache->setDefinition($this->definition);
    }

    public function testReturnDefinition()
    {
        $this->assertSame(
            $this->definition,
            $this->cache->getDefinition()
        );
    }

    public function testDefinitionIsAnArray()
    {
        $this->assertTrue(
            is_array($this->cache->getDefinition())
        );
    }
}
