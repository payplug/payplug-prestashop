<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getPriceLimitTest extends BasePaymentMethod
{
    public function testWhenPaymentMethodHasItsOwnLimits()
    {
        $this->class->set('name', 'giropay');

        $this->assertSame(
            [
                'min' => 'EUR:100',
                'max' => 'EUR:1000000',
            ],
            $this->class->getPriceLimit()
        );
    }

    public function testWhenPaymentMethodHasNoSpecificLimits()
    {
        $this->class->set('name', 'wero');

        $this->assertSame(
            [
                'min' => 'EUR:30',
                'max' => 'EUR:2000000',
            ],
            $this->class->getPriceLimit()
        );
    }
}
