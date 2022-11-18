<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetLimitNumberLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setLimitNumber(42);
    }

    public function testReturntLimitNumber()
    {
        $this->assertSame(
            42,
            $this->logger->getLimitNumber()
        );
    }

    public function testtLimitNumberIsAnInt()
    {
        $this->assertTrue(
            is_int($this->logger->getLimitNumber())
        );
    }
}
