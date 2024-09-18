<?php

namespace PayPlug\tests\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class GetIdCartTest extends BaseLockEntity
{
    private $id;

    public function setUp()
    {
        parent::setUp();
        $this->id = 42;
        $this->entity->setIdCart($this->id);
    }

    public function testReturnId()
    {
        $this->assertSame(
            $this->id,
            $this->entity->getIdCart()
        );
    }

    public function testIdIsAnInteger()
    {
        $this->assertTrue(
            is_int($this->entity->getIdCart())
        );
    }
}
