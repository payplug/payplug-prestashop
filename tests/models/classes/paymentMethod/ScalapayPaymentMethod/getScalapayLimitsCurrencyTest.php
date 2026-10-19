<?php

namespace PayPlug\tests\models\classes\paymentMethod\ScalapayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group scalapay_payment_method_class
 */
class getScalapayLimitsCurrencyTest extends BaseScalapayPaymentMethod
{
    public function testCurrencyOfTheAccountLimitsIsReturned()
    {
        $this->assertSame('EUR', $this->class->getScalapayLimitsCurrency());
    }
}
