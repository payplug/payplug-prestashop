<?php

namespace PayPlug\tests\models\entities\QueueEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class SetIdCartTest extends BaseQueueEntity
{
    public function testUpdateIdCart()
    {
        $this->entity->setIdCart($this->id_cart);
        $this->assertSame(
            $this->id_cart,
            $this->entity->getIdCart()
        );
    }

    public function testReturnQueueEntity()
    {
        $this->assertInstanceOf(
            QueueEntity::class,
            $this->entity->setIdCart($this->id_cart)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testThrowExceptionWhenNotAnInteger($id_cart)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateUpd($id_cart);
    }
}
