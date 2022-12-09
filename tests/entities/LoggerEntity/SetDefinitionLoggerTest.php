<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetDefinitionLoggerTest extends TestCase
{
    protected $logger;
    protected $definition;
    protected $definition_alt;

    protected function setUp()
    {
        $this->logger = new LoggerEntity();
        $this->definition = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->definition_alt = [
            'keyA' => 'valueA',
            'keyB' => 'valueB',
            'keyC' => 8,
        ];
        $this->logger->setDefinition($this->definition);
    }

    public function testUpdateDefinition()
    {
        $this->logger->setDefinition($this->definition_alt);
        $this->assertSame(
            $this->definition_alt,
            $this->logger->getDefinition()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->logger->setDefinition($this->definition_alt)
        );
    }

    /**
     * @group entity_exception
     * @group logger_exception
     * @group logger_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAnArray()
    {
        $this->expectException(BadParameterException::class);
        $this->logger->setDefinition(42);
    }
}
