<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetDateAddLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setDateAdd('2021-12-31 23:59:42');
    }

    public function testReturnDateAdd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->logger->getDateAdd()
        );
    }

    public function testDateAddIsAString()
    {
        $this->assertTrue(
            is_string($this->logger->getDateAdd())
        );
    }

    public function testDateAddHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->logger->getDateAdd()
        );
    }
}
