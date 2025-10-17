<?php

namespace PayPlug\tests\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class GetDefinitionTest extends BaseLockEntity
{
    protected $definition;

    public function setUp()
    {
        parent::setUp();
        $this->definition = [
            'table' => 'payplug_lock',
            'primary' => 'id_payplug_lock',
            'fields' => [
                'id_cart' => ['type' => 'integer', 'required' => true],
                'id_order' => ['type' => 'string', 'required' => true],
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
