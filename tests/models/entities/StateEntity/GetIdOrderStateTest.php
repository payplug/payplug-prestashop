<?php

namespace PayPlug\tests\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class getIdOrderStateTest extends BaseStateEntity
{
    protected $state;

    public function setUp()
    {
        parent::setUp();
        $this->entity->setIdOrderState(42);
    }

    public function testReturnId()
    {
        $this->assertSame(
            42,
            $this->entity->getIdOrderState()
        );
    }

    public function testIdIsAnInteger()
    {
        $this->assertTrue(
            is_int($this->entity->getIdOrderState())
        );
    }
}
