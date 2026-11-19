<?php

namespace PayPlug\tests\models\classes\paymentMethod\EmailLinkPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\EmailLinkPaymentMethod;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group email_link_payment_method_class
 */
class getPaymentOptionTest extends BasePaymentMethod
{
    /**
     * @dataProvider cartCurrencyDataProvider
     *
     * @param string $iso_code
     */
    public function testNoPaymentOptionIsAddedAtCheckout($iso_code)
    {
        $payment_options = ['standard' => ['name' => 'standard']];
        $this->context->currency->iso_code = $iso_code;
        $class = \Mockery::mock(EmailLinkPaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->assertSame($payment_options, $class->getPaymentOption($payment_options));
    }

    public function cartCurrencyDataProvider()
    {
        yield ['EUR'];

        yield ['USD'];
    }
}
