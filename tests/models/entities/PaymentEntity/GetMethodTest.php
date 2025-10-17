<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class GetMethodTest extends BasePaymentEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setMethod($this->method);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->method,
            $this->entity->getMethod()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getMethod())
        );
    }
}
