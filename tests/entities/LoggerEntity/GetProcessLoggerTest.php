<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetProcessLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setProcess('process');
    }

    public function testReturnProcess()
    {
        $this->assertSame(
            'process',
            $this->logger->getProcess()
        );
    }

    public function testProcessIsAString()
    {
        $this->assertTrue(
            is_string($this->logger->getProcess())
        );
    }
}
