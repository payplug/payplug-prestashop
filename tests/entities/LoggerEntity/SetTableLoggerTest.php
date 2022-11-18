<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetTableLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setTable('test_table');
    }

    public function testUpdatePaymentTable()
    {
        $this->logger->setTable('another_table');
        $this->assertSame(
            'another_table',
            $this->logger->getTable()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->logger->setTable('another_table')
        );
    }

    /**
     * @group entity_exception
     * @group logger_exception
     * @group logger_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->logger->setTable(42);
    }
}
