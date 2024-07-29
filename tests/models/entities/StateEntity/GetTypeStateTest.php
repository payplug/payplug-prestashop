<?php

namespace PayPlug\tests\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class GetTypeStateTest extends BaseStateEntity
{
    protected $state;

    public function setUp()
    {
        parent::setUp();
        $this->entity->setType('type');
    }

    public function testReturnType()
    {
        $this->assertSame(
            'type',
            $this->entity->getType()
        );
    }

    public function testTypeIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getType())
        );
    }
}
