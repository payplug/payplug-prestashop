<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetTypeLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setType('type');
    }

    public function testReturnType()
    {
        $this->assertSame(
            'type',
            $this->logger->getType()
        );
    }

    public function testTypeIsAString()
    {
        $this->assertTrue(
            is_string($this->logger->getType())
        );
    }
}
