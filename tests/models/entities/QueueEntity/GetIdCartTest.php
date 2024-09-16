<?php

namespace PayPlug\tests\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class GetIdCartTest extends BaseQueueEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setIdCart($this->id_cart);
    }

    public function testReturnIdCart()
    {
        $this->assertSame(
            $this->id_cart,
            $this->entity->getIdCart()
        );
    }

    public function testIdCartIsAnInteger()
    {
        $this->assertTrue(
            is_int($this->entity->getIdCart())
        );
    }
}
