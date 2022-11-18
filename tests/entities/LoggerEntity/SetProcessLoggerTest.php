<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetProcessLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setProcess('test_process');
    }

    public function testUpdateProcess()
    {
        $this->logger->setProcess('another_process');
        $this->assertSame(
            'another_process',
            $this->logger->getProcess()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->logger->setProcess('another_process')
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
        $this->logger->setProcess(42);
    }
}
