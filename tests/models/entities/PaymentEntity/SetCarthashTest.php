<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class SetCarthashTest extends BasePaymentEntity
{
    public function testUpdateCartHash()
    {
        $this->entity->setCarthash($this->cart_hash);
        $this->assertSame(
            $this->cart_hash,
            $this->entity->getCarthash()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            PaymentEntity::class,
            $this->entity->setCarthash($this->cart_hash)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $cart_hash
     */
    public function testThrowExceptionWhenNotAString($cart_hash)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setCarthash($cart_hash);
    }
}
