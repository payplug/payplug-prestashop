<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetDefinitionTest extends BaseLoggerEntity
{
    protected $definition;

    public function setUp()
    {
        parent::setUp();
        $this->definition = [
            'table' => 'payplug_logger',
            'primary' => 'id_payplug_logger',
            'fields' => [
                'process' => ['type' => 'string', 'required' => true],
                'content' => ['type' => 'string', 'required' => true],
                'date_add' => ['type' => 'string'],
                'date_upd' => ['type' => 'string'],
            ],
        ];
    }

    public function testReturnDefinition()
    {
        $this->assertSame(
            $this->definition,
            $this->entity->getDefinition()
        );
    }

    public function testDefinitionIsAnArray()
    {
        $this->assertTrue(
            is_array($this->entity->getDefinition())
        );
    }
}
