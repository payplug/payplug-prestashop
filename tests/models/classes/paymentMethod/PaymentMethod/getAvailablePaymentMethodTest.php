<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getAvailablePaymentMethodTest extends BasePaymentMethod
{
    public function testWhenFeatureHostedFieldsIsActiveAndShopHasNoEurCurrency()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($feature) {
                return 'feature_hosted_fields' == $feature;
            });
        $this->dependencies->configClass = $configClass;

        $this->currencies = [['iso_code' => 'USD']];
        $this->has_eur_currency = false;

        $this->assertSame(
            [
                'standard',
            ],
            $this->class->getAvailablePaymentMethod()
        );
    }

    public function testWhenFeatureHostedFieldsIsInactiveAndShopHasNoEurCurrency()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->dependencies->configClass = $configClass;

        $this->currencies = [['iso_code' => 'USD']];
        $this->has_eur_currency = false;

        $this->assertSame(
            [
                'one_click',
                'standard',
                'installment',
                'amex',
                'applepay',
                'bancontact',
                'satispay',
                'mybank',
                'ideal',
                'oney',
                'email_link',
                'sms_link',
                'wero',
                'bizum',
                'scalapay',
            ],
            $this->class->getAvailablePaymentMethod()
        );
    }

    public function testWhenFeatureHostedFieldsIsActiveAndShopHasEurCurrency()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($feature) {
                return 'feature_hosted_fields' == $feature;
            });
        $this->dependencies->configClass = $configClass;

        $this->currencies = [['iso_code' => 'EUR']];
        $this->has_eur_currency = true;

        $this->assertSame(
            [
                'one_click',
                'standard',
                'installment',
                'amex',
                'applepay',
                'bancontact',
                'satispay',
                'mybank',
                'ideal',
                'oney',
                'email_link',
                'sms_link',
                'wero',
                'bizum',
                'scalapay',
            ],
            $this->class->getAvailablePaymentMethod()
        );
    }
}
