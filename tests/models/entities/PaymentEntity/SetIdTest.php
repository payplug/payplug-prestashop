<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class SetIdTest extends BasePaymentEntity
{
    public function testUpdateId()
    {
        $this->entity->setId($this->id);
        $this->assertSame(
            $this->id,
            $this->entity->getId()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            PaymentEntity::class,
            $this->entity->setId($this->id)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id
     */
    public function testThrowExceptionWhenNotAnInteger($id)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setId($id);
    }
}
