<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetLimitNumberLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setLimitNumber(42);
    }

    public function testUpdateLimitNumber()
    {
        $this->logger->setLimitNumber(24);
        $this->assertSame(
            24,
            $this->logger->getLimitNumber()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->logger->setLimitNumber(24)
        );
    }

    /**
     * @group entity_exception
     * @group logger_exception
     * @group logger_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAnInt()
    {
        $this->expectException(BadParameterException::class);
        $this->logger->setLimitNumber('wrong_parameter');
    }
}
