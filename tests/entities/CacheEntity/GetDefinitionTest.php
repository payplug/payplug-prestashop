<?php declare(strict_types=1);

use PayPlug\src\entities\CacheEntity;
use PHPUnit\Framework\TestCase;

final class GetDefinitionTest extends TestCase
{
    protected $cache;
    protected $definition;

    protected function setUp(): void
    {
        $this->cache = new CacheEntity();
        $this->definition = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->cache->setDefinition($this->definition);
    }

    public function testReturnDefinition(): void
    {
        $this->assertSame(
            $this->definition,
            $this->cache->getDefinition()
        );
    }
}
