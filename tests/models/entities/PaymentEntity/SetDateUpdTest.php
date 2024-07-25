<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class SetDateUpdTest extends BasePaymentEntity
{
    public function testUpdateDateUpd()
    {
        $this->entity->setDateUpd('1920-12-31 23:59:42');
        $this->assertSame(
            '1920-12-31 23:59:42',
            $this->entity->getDateUpd()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            PaymentEntity::class,
            $this->entity->setDateUpd('1920-12-31 23:59:42')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date
     */
    public function testThrowExceptionWhenNotAString($date)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateUpd($date);
    }

    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateUpd('1er Janvier 1970');
    }
}
