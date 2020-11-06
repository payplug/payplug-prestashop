<?php declare(strict_types=1);

use PayPlug\src\entities\PluginEntity;
use PayPlug\src\entities\QueryEntity;
use PHPUnit\Framework\TestCase;

final class SetQueryTest extends TestCase
{
    public function test_update_the_query_entity(): void
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