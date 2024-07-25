<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class GetDateUpdTest extends BasePaymentEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setDateUpd($this->date);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->date,
            $this->entity->getDateUpd()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getDateUpd())
        );
    }

    public function testDateUpdHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->entity->getDateUpd()
        );
    }
}
