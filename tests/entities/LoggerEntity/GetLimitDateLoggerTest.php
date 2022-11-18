<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetLimitDateLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setLimitDate('limit_date');
    }

    public function testReturntLimitDate()
    {
        $this->assertSame(
            'limit_date',
            $this->logger->getLimitDate()
        );
    }

    public function testtLimitDateIsAnInt()
    {
        $this->assertTrue(
            is_string($this->logger->getLimitDate())
        );
    }
}
