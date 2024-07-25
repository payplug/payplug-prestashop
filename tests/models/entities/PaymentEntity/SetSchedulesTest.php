<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class SetSchedulesTest extends BasePaymentEntity
{
    public function testUpdateCartHash()
    {
        $this->entity->setSchedules($this->schedules);
        $this->assertSame(
            $this->schedules,
            $this->entity->getSchedules()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            PaymentEntity::class,
            $this->entity->setSchedules($this->schedules)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $schedules
     */
    public function testThrowExceptionWhenNotAString($schedules)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setSchedules($schedules);
    }
}
