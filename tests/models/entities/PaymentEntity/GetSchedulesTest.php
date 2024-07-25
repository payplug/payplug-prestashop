<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class GetSchedulesTest extends BasePaymentEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setSchedules($this->schedules);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->schedules,
            $this->entity->getSchedules()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getSchedules())
        );
    }
}
