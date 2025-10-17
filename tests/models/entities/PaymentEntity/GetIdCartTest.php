<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class GetIdCartTest extends BasePaymentEntity
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
