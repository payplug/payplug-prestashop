<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class SetMethodTest extends BasePaymentEntity
{
    public function testUpdateCartHash()
    {
        $this->entity->setMethod($this->method);
        $this->assertSame(
            $this->method,
            $this->entity->getMethod()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            PaymentEntity::class,
            $this->entity->setMethod($this->method)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method
     */
    public function testThrowExceptionWhenNotAString($method)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setMethod($method);
    }
}
