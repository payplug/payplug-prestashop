<?php

namespace PayPlug\tests\application\adapter\QueryAdapter;

use PayPlug\src\application\adapter\QueryAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group adapter
 * @group query_adapter
 */
class QueryAdapterTest extends TestCase
{
    protected $adapter;
    protected $db;

    public function setUp(): void
    {
        $this->db = \Mockery::mock('Db');
        // Built without its constructor, which needs a live \Db instance
        $this->adapter = (new \ReflectionClass(QueryAdapter::class))->newInstanceWithoutConstructor();
        $property = new \ReflectionProperty(QueryAdapter::class, 'db');
        $property->setAccessible(true);
        $property->setValue($this->adapter, $this->db);
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testSelectUsesTheSqlCacheByDefault()
    {
        $this->db->shouldReceive('executeS')
            ->with('SELECT 1', true, true)
            ->once()
            ->andReturn([]);

        $this->assertSame([], $this->adapter->query('SELECT 1'));
    }

    public function testSelectCanBypassTheSqlCache()
    {
        $this->db->shouldReceive('executeS')
            ->with('SELECT 1', true, false)
            ->once()
            ->andReturn([]);

        $this->assertSame([], $this->adapter->query('SELECT 1', false));
    }

    public function testWriteQueryKeepsUsingExecute()
    {
        $this->db->shouldReceive('execute')
            ->with('DELETE FROM t', true)
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->adapter->query('DELETE FROM t'));
    }

    public function testLockValueBypassesTheSqlCache()
    {
        $this->db->shouldReceive('getValue')
            ->with('SELECT GET_LOCK(1)', false)
            ->once()
            ->andReturn('1');

        $this->assertSame('1', $this->callGetLockValue('SELECT GET_LOCK(1)'));
        $this->assertSame('', $this->adapter->getLastError());
    }

    public function testLockValueKeepsTheMysqlErrorWhenTheQueryFails()
    {
        $this->db->shouldReceive([
            'getValue' => false,
            'getMsgError' => 'Lost connection',
        ]);

        $this->assertFalse($this->callGetLockValue('SELECT GET_LOCK(1)'));
        $this->assertSame('Lost connection', $this->adapter->getLastError());
    }

    public function testLockValueKeepsTheExceptionMessage()
    {
        $this->db->shouldReceive('getValue')
            ->andThrow(new \Exception('Access denied'));

        $this->assertFalse($this->callGetLockValue('SELECT GET_LOCK(1)'));
        $this->assertSame('Access denied', $this->adapter->getLastError());
    }

    public function testLastErrorIsClearedByTheNextSuccessfulCall()
    {
        $this->db->shouldReceive('getValue')
            ->andReturn(false, '1');
        $this->db->shouldReceive([
            'getMsgError' => 'Lost connection',
        ]);

        $this->callGetLockValue('SELECT GET_LOCK(1)');
        $this->callGetLockValue('SELECT GET_LOCK(1)');

        $this->assertSame('', $this->adapter->getLastError());
    }

    /**
     * @param string $sql
     *
     * @return mixed
     */
    private function callGetLockValue($sql)
    {
        $method = new \ReflectionMethod(QueryAdapter::class, 'getLockValue');
        $method->setAccessible(true);

        return $method->invoke($this->adapter, $sql);
    }
}
