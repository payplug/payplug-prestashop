<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getPaymentOptionTest extends BasePaymentMethod
{
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
            ->with('currencies')
            ->andReturn('EUR;JPY');
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

    public function testCartWithoutCurrencyHidesThePaymentMethod()
    {
        $this->class->set('name', 'standard');
        $this->context->currency = null;
        $this->allowCountryAndAmount();

        $this->assertSame([], $this->class->getPaymentOption([]));
        $this->helpers['amount']->shouldNotHaveReceived('validateAmount');
    }

    public function testCartCurrencyNotSupportedByTheMerchantHidesThePaymentMethod()
    {
        $payment_options = ['standard' => ['name' => 'standard']];
        $this->class->set('name', 'bancontact');
        $this->context->currency->iso_code = 'USD';
        $this->allowCountryAndAmount();

        $this->assertSame($payment_options, $this->class->getPaymentOption($payment_options));
        $this->helpers['amount']->shouldNotHaveReceived('validateAmount');
        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::pattern('/bancontact.*USD/'), 'info')
            ->once();
    }

    public function testCartCurrencyAmongTheMerchantCurrenciesShowsThePaymentMethod()
    {
        $this->class->set('name', 'bancontact');
        $this->context->currency->iso_code = 'GBP';
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR;GBP');
        $this->allowCountryAndAmount();

        $this->assertArrayHasKey('bancontact', $this->class->getPaymentOption([]));
    }

    public function testStandardIsShownForAnUnsupportedCurrencyServedByTheHostedFields()
    {
        $this->class->set('name', 'standard');
        $this->context->currency->iso_code = 'USD';
        $this->allowCountryAndAmount();
        $this->setHostedFieldsConfiguration(true, 'integrated', true);

        $this->assertArrayHasKey('standard', $this->class->getPaymentOption([]));
    }

    /**
     * @dataProvider hostedFieldsNotApplicableDataProvider
     *
     * @param string $name
     * @param bool $feature_enabled
     * @param string $embedded_mode
     * @param bool $identifier_configured
     */
    public function testPaymentMethodIsHiddenForAnUnsupportedCurrencyNotServedByTheHostedFields(
        $name,
        $feature_enabled,
        $embedded_mode,
        $identifier_configured
    ) {
        $payment_options = ['one_click_1' => ['name' => 'one_click']];
        $this->class->set('name', $name);
        $this->context->currency->iso_code = 'USD';
        $this->allowCountryAndAmount();
        $this->setHostedFieldsConfiguration($feature_enabled, $embedded_mode, $identifier_configured);

        $this->assertSame($payment_options, $this->class->getPaymentOption($payment_options));
    }

    public function hostedFieldsNotApplicableDataProvider()
    {
        yield 'hosted fields feature disabled' => ['standard', false, 'integrated', true];

        yield 'popup embedded mode' => ['standard', true, 'popup', true];

        yield 'redirect embedded mode' => ['standard', true, 'redirect', true];

        yield 'no identifier for the cart currency' => ['standard', true, 'integrated', false];

        yield 'payment method other than standard' => ['bancontact', true, 'integrated', true];
    }

    public function testStandardIsHiddenForAnUnsupportedCurrencyWhenThePrestashopAdapterCannotBeLoaded()
    {
        $this->class->set('name', 'standard');
        $this->context->currency->iso_code = 'USD';
        $this->allowCountryAndAmount();
        $this->setHostedFieldsConfiguration(true, 'integrated', true);
        $this->dependencies->shouldReceive('loadAdapterPresta')->andReturn(false);

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    private function allowCountryAndAmount()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);
    }

    private function setHostedFieldsConfiguration($feature_enabled, $embedded_mode, $identifier_configured)
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')
            ->andReturn($feature_enabled);
        $this->dependencies->configClass = $configClass;
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn($embedded_mode);
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $prestashop_adapter->shouldReceive('isHostedFieldsIdentifierConfigured')
            ->with('USD')
            ->andReturn($identifier_configured);
        $this->dependencies->shouldReceive('loadAdapterPresta')
            ->andReturn($prestashop_adapter)
            ->byDefault();
    }
}
