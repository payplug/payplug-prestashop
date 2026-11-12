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
        $this->logger->shouldNotHaveReceived('addLog');
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
        $prestashop_adapter = $this->setHostedFieldsAdapter(true);

        $this->assertArrayHasKey('standard', $this->class->getPaymentOption([]));
        $prestashop_adapter->shouldHaveReceived('canServeHostedFields')->with('USD')->once();
    }

    /**
     * @dataProvider nonIntegratedEmbeddedModeDataProvider
     *
     * @param string $embedded_mode
     */
    public function testStandardIsShownForAnUnsupportedCurrencyServedByTheHostedFieldsInAShopWithEurWhateverTheEmbeddedMode(
        $embedded_mode
    ) {
        $this->class->set('name', 'standard');
        $this->context->currency->iso_code = 'USD';
        $this->has_eur_currency = true;
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn($embedded_mode);
        $this->allowCountryAndAmount();
        $this->setHostedFieldsAdapter(true);

        $this->assertArrayHasKey('standard', $this->class->getPaymentOption([]));
    }

    public function nonIntegratedEmbeddedModeDataProvider()
    {
        yield 'popup' => ['popup'];

        yield 'redirect' => ['redirect'];
    }

    public function testStandardIsHiddenForAnUnsupportedCurrencyNotServedByTheHostedFields()
    {
        $payment_options = ['one_click_1' => ['name' => 'one_click']];
        $this->class->set('name', 'standard');
        $this->context->currency->iso_code = 'USD';
        $this->allowCountryAndAmount();
        $this->setHostedFieldsAdapter(false);

        $this->assertSame($payment_options, $this->class->getPaymentOption($payment_options));
    }

    public function testThePrestashopAdapterIsNotLoadedForAPaymentMethodOtherThanStandard()
    {
        $this->class->set('name', 'bancontact');
        $this->context->currency->iso_code = 'USD';
        $this->allowCountryAndAmount();
        $this->setHostedFieldsAdapter(true);

        $this->assertSame([], $this->class->getPaymentOption([]));
        $this->dependencies->shouldNotHaveReceived('loadAdapterPresta');
    }

    public function testStandardIsHiddenForAnUnsupportedCurrencyWhenThePrestashopAdapterCannotBeLoaded()
    {
        $this->class->set('name', 'standard');
        $this->context->currency->iso_code = 'USD';
        $this->allowCountryAndAmount();
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

    private function setHostedFieldsAdapter($can_serve)
    {
        $prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $prestashop_adapter->shouldReceive('canServeHostedFields')
            ->with('USD')
            ->andReturn($can_serve);
        $this->dependencies->shouldReceive('loadAdapterPresta')
            ->andReturn($prestashop_adapter);

        return $prestashop_adapter;
    }
}
