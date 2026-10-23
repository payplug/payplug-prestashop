<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getPaymentOptionTest extends BasePaymentMethod
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function testWhenGivenPaymentOptionsIsntValidArray($payment_options)
    {
        $this->assertSame([], $this->class->getPaymentOption($payment_options));
    }

    public function testWhenNoNameFoundForPaymentMethod()
    {
        $this->class->set('name', '');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testWhenPaymentMethodDoesNotMatchWithCurrentAddressCountry()
    {
        $this->class->set('name', 'standard');
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{"standard":["FR"]}');

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'BE',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testWhenPaymentMethodMatchWithCurrentAddressCountry()
    {
        $this->class->set('name', 'standard');
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{"standard":["FR"]}');
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertTrue(array_key_exists('standard', $this->class->getPaymentOption()));
    }

    public function testWhenPaymentMethodHasNoCountryRestriction()
    {
        $this->class->set('name', 'standard');
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertTrue(array_key_exists('standard', $this->class->getPaymentOption()));
    }

    public function testAmountIsValidatedInTheCartCurrency()
    {
        $this->class->set('name', 'standard');
        $this->context->currency->iso_code = 'JPY';
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);

        $this->class->getPaymentOption([]);

        $this->helpers['amount']->shouldHaveReceived('validateAmount')
            ->with(\Mockery::type('array'), 42.42, 'JPY')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testCartWithoutCurrencyIsValidatedWithoutCurrency()
    {
        $this->class->set('name', 'standard');
        $this->context->currency = null;
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => false,
                'message' => '',
            ]);

        $this->assertSame([], $this->class->getPaymentOption([]));

        $this->helpers['amount']->shouldHaveReceived('validateAmount')
            ->with(\Mockery::type('array'), 42.42, '')
            ->once();
    }

    public function testPproIsShownWhenAmountAndBillingCountryAreEligible()
    {
        $this->class->set('name', 'satispay');
        $this->stubPproContext('{"satispay":["IT"]}', 'IT');
        $this->api_service->shouldNotReceive('getAccount');

        $this->assertTrue(array_key_exists('satispay', $this->class->getPaymentOption([])));
    }

    public function testPproIsHiddenWhenBillingCountryIsNotAllowed()
    {
        $this->class->set('name', 'satispay');
        $this->stubPproContext('{"satispay":["IT"]}', 'FR');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testPproIsHiddenWhenCartAmountIsAboveTheMerchantMaximum()
    {
        $this->class->set('name', 'satispay');
        $this->stubPproContext('{}', 'IT');
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"satispay":{"max":{"EUR":4000}}}');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testPproIsHiddenWhenCartAmountIsBelowTheApiMinimum()
    {
        $this->class->set('name', 'scalapay');
        $this->stubPproContext('{}', 'IT');
        $this->configuration->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn('{"scalapay":{"min":"EUR:5000","max":"EUR:400000"}}');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testScalapayIsHiddenWhenCartAmountIsAboveTheMerchantMaximum()
    {
        $this->class->set('name', 'scalapay');
        $this->stubPproContext('{}', 'IT');
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"scalapay":{"max":{"EUR":4000}}}');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testScalapayIgnoresTheLegacyCustomAmounts()
    {
        $this->class->set('name', 'scalapay');
        $this->stubPproContext('{}', 'IT');
        $this->configuration->shouldReceive('getValue')
            ->with(\Mockery::anyOf('scalapay_custom_min_amounts', 'scalapay_custom_max_amounts'))
            ->andReturn('EUR:5000');

        $this->assertTrue(array_key_exists('scalapay', $this->class->getPaymentOption([])));
    }

    public function testPproIsHiddenWhenTheMerchantSelectedNoCountry()
    {
        $this->class->set('name', 'bancontact');
        $this->stubPproContext('{}', 'BE');
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"bancontact":{"countries":[]}}');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testPproIsHiddenUntilACustomerHasABillingAddress()
    {
        $this->class->set('name', 'bancontact');
        $this->stubPproContext('{}', 'BE');
        $this->context->cart->id_address_invoice = 0;

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testPproIsHiddenWhenTheApiExposesNoLimitsForIt()
    {
        $this->class->set('name', 'wero');
        $this->stubPproContext('{}', 'BE');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testPproIsCheckedWithTheCartAmountCurrencyAndBillingCountry()
    {
        $this->class->set('name', 'satispay');
        $this->stubPproContext('{}', 'IT');
        $apm_eligibility = \Mockery::mock('ApmEligibility');
        $apm_eligibility->shouldReceive('isPpro')
            ->with('satispay')
            ->andReturn(true);
        $apm_eligibility->shouldReceive('isEligible')
            ->once()
            ->with('satispay', 4242, 'EUR', 'IT')
            ->andReturn(['result' => false, 'reason' => 'country']);
        $this->class->set('apm_eligibility', $apm_eligibility);
        $this->helpers['amount']->shouldNotReceive('validateAmount');

        $this->assertSame([], $this->class->getPaymentOption([]));
        $this->amountHelper->shouldHaveReceived('convertAmount')
            ->with(42.42, false, 'EUR')
            ->once();
    }

    public function testCardMethodsAreNotHiddenWhenNoLimitWasEverCached()
    {
        $this->class->set('name', 'standard');
        $this->stubPproContext('{}', 'FR');
        $this->configuration->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn('{}');

        $this->assertTrue(array_key_exists('standard', $this->class->getPaymentOption([])));
    }

    public function testCardMethodsKeepTheDefaultAmountRange()
    {
        $this->class->set('name', 'standard');
        $this->stubPproContext('{}', 'FR');
        $this->configuration->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn('{"default":{"min":"EUR:99","max":"EUR:4000"}}');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    /**
     * @param string $countries
     * @param string $billing_iso
     */
    private function stubPproContext($countries, $billing_iso)
    {
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn($countries);
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => $billing_iso,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->helpers['amount']->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $from_cents = false, $currency = '') {
                return '' === $currency ? 0 : (int) round($amount * 100);
            });
    }
}
