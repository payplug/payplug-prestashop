<?php

namespace PayPlug\tests\models\classes\paymentMethod\ScalapayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group scalapay_payment_method_class
 */
class setCustomScalapayLimitTest extends BaseScalapayPaymentMethod
{
    public function testWhenAmountIsFormattedWithTheAccountCurrency()
    {
        $this->assertSame(
            'EUR:1000',
            $this->class->setCustomScalapayLimit(1000)
        );
    }

    public function testWhenAmountIsNotAnInteger()
    {
        $this->assertSame(
            'EUR:1000',
            $this->class->setCustomScalapayLimit('1000')
        );
    }
}
