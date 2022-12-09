<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetContentLoggerTest extends TestCase
{
    protected $logger;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->logger->setContent('content');
    }

    public function testReturnContent()
    {
        $this->assertSame(
            'content',
            $this->logger->getContent()
        );
    }

    public function testContentIsAString()
    {
        $this->assertTrue(
            is_string($this->logger->getContent())
        );
    }
}
