<?php

namespace PayPlug\tests\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class GetIdOrderTest extends BaseLockEntity
{
    private $id_order;

    public function setUp()
    {
        parent::setUp();
        $this->id_order = 'order_id';
        $this->entity->setIdOrder($this->id_order);
    }

    public function testReturnId()
    {
        $this->assertSame(
            $this->id_order,
            $this->entity->getIdOrder()
        );
    }

    public function testIdIsAnInteger()
    {
        $this->assertTrue(
            is_string($this->entity->getIdOrder())
        );
    }
}
