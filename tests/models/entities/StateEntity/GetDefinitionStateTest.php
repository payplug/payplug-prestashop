<?php

namespace PayPlug\tests\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class GetDefinitionStateTest extends BaseStateEntity
{
    protected $definition;

    public function setUp()
    {
        parent::setUp();
        $this->definition = [
            'table' => 'payplug_order_state',
            'primary' => 'id_payplug_order_state',
            'fields' => [
                'id_order_state' => ['type' => 'integer', 'required' => true],
                'type' => ['type' => 'string', 'required' => true],
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
