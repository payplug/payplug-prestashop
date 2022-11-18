<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetTableLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setTable('test_table');
    }

    public function testReturnTable()
    {
        $this->assertSame(
            'test_table',
            $this->logger->getTable()
        );
    }

    public function testTableIsAString()
    {
        $this->assertTrue(
            is_string($this->logger->getTable())
        );
    }
}
