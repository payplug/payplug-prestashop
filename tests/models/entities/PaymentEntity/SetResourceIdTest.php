<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class SetResourceIdTest extends BasePaymentEntity
{
    public function testUpdateCartHash()
    {
        $this->entity->setResourceId($this->resource_id);
        $this->assertSame(
            $this->resource_id,
            $this->entity->getResourceId()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            PaymentEntity::class,
            $this->entity->setResourceId($this->resource_id)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testThrowExceptionWhenNotAString($resource_id)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setResourceId($resource_id);
    }
}
