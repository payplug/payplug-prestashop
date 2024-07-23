<?php

namespace PayPlug\tests\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class getIdTest extends BaseStateEntity
{
    protected $state;

    public function setUp()
    {
        parent::setUp();
        $this->entity->setId(42);
    }

    public function testReturnId()
    {
        $this->assertSame(
            42,
            $this->entity->getId()
        );
    }

    public function testIdIsAString()
    {
        $this->assertTrue(
            is_int($this->entity->getId())
        );
    }
}
