<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class GetCartHashTest extends BasePaymentEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setCartHash($this->cart_hash);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->cart_hash,
            $this->entity->getCartHash()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getCartHash())
        );
    }
}
