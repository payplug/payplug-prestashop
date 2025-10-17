<?php

namespace PayPlug\tests\models\entities\CacheEntity;

/**
 * @group entity
 * @group cache
 * @group cache_entity
 */
final class GetDefinitionCacheTest extends BaseCacheEntity
{
    protected $definition;

    public function setUp()
    {
        parent::setUp();
        $this->definition = [
            'table' => 'payplug_cache',
            'primary' => 'id_payplug_cache',
            'fields' => [
                'cache_key' => ['type' => 'string', 'required' => true],
                'cache_value' => ['type' => 'string', 'required' => true],
                'date_add' => ['type' => 'string'],
                'date_upd' => ['type' => 'string'],
            ],
        ];
//        $this->cache->setDefinition($this->definition);
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

    public function testDefinitionTableIsString()
    {
        $definition = $this->cache->getDefinition();
        $this->assertTrue(
            is_string($this->definition['table'])
        );
    }

    public function testDefinitionPrimaryIsString()
    {
        $definition = $this->cache->getDefinition();
        $this->assertTrue(
            is_string($definition['primary'])
        );
    }

    public function testDefinitionHasTableKey()
    {
        $definition = $this->cache->getDefinition();
        $this->assertArrayHasKey('table', $definition);
    }

    public function testDefinitionHasPrimaryKey()
    {
        $definition = $this->cache->getDefinition();
        $this->assertArrayHasKey('primary', $definition);
    }

    public function testDefinitionHasFieldsKey()
    {
        $definition = $this->cache->getDefinition();
        $this->assertArrayHasKey('fields', $definition);
    }
}
