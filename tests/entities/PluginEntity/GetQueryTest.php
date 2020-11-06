<?php declare(strict_types=1);

use PayPlug\src\entities\PluginEntity;
use PayPlug\src\entities\QueryEntity;
use PHPUnit\Framework\TestCase;

final class GetQueryTest extends TestCase
{
    public function test_return_a_query_entity(): void
    {
        $plugin = new PluginEntity();
        $query = new QueryEntity();
        $plugin->setQuery($query);
        $this->assertInstanceOf(
            QueryEntity::class,
            $plugin->getQuery()
        );
    }
}