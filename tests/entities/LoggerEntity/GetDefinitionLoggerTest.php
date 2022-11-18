<?php

use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetDefinitionLoggerTest extends TestCase
{
    protected $logger;
    protected $definition;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->definition = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->logger->setDefinition($this->definition);
    }

    public function testReturnDefinition()
    {
        $this->assertSame(
            $this->definition,
            $this->logger->getDefinition()
        );
    }

    public function testDefinitionIsAnArray()
    {
        $this->assertTrue(
            is_array($this->logger->getDefinition())
        );
    }
}
