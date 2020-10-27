<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class SetDefinitionTest extends TestCase
{
    protected $cache;
    protected $definition;
    protected $definition_alt;

    protected function setUp(): void
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

    public function testUpdateDefinition(): void
    {
        $this->cache->setDefinition($this->definition_alt);
        $this->assertSame(
            $this->definition_alt,
            $this->cache->getDefinition()
        );
    }
}
