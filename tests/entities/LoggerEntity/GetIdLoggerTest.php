<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetIdLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setId('id');
    }

    public function testReturnId()
    {
        $this->assertSame(
            'id',
            $this->logger->getId()
        );
    }

    public function testIdIsAString()
    {
        $this->assertTrue(
            is_string($this->logger->getId())
        );
    }
}
