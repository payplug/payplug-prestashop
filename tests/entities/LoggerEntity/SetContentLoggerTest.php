<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetContentLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setContent('test_content');
    }

    public function testUpdateContent()
    {
        $this->logger->setContent('another_content');
        $this->assertSame(
            'another_content',
            $this->logger->getContent()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->logger->setContent('another_content')
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
        $this->logger->setContent(42);
    }
}
