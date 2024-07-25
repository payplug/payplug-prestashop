<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class SetIdCartTest extends BasePaymentEntity
{
    public function testUpdateIdCart()
    {
        $this->entity->setIdCart($this->id_cart);
        $this->assertSame(
            $this->id_cart,
            $this->entity->getIdCart()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            PaymentEntity::class,
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
