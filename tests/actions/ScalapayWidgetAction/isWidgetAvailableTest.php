<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class isWidgetAvailableTest extends BaseScalapayWidgetAction
{
    public function pageProvider()
    {
        yield ['product'];

        yield ['cart'];

        yield ['checkout'];
    }

    /**
     * @dataProvider pageProvider
     *
     * @param string $page
     */
    public function testTheWidgetIsAvailableWhenEveryConditionIsMet($page)
    {
        $this->assertTrue($this->action->isWidgetAvailable($page));
        $this->assertTrue($this->action->isWidgetAvailable($page, 150.0));
    }

    public function testAnUnknownPageIsRefused()
    {
        $this->assertFalse($this->action->isWidgetAvailable('home'));
    }

    public function testTheFeatureFlagIsRequired()
    {
        $this->config_class->shouldReceive('isValidFeature')->with('feature_scalapay')->andReturn(false);

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function testTheModuleMustBeAllowed()
    {
        $this->config_class->shouldReceive('isAllowed')->andReturn(false);

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function disabledPaymentMethodProvider()
    {
        yield 'off' => ['{"scalapay":false}'];

        yield 'absent' => ['{"standard":true}'];

        yield 'malformed' => ['{'];

        yield 'missing row' => [false];
    }

    /**
     * @dataProvider disabledPaymentMethodProvider
     *
     * @param mixed $payment_methods
     */
    public function testScalapayMustBeEnabled($payment_methods)
    {
        $this->stored['payment_methods'] = $payment_methods;

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function testNoWidgetInSandboxMode()
    {
        $this->stored['sandbox_mode'] = '1';

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function missingTokenProvider()
    {
        yield 'empty' => [''];

        yield 'blank' => ['  '];

        yield 'missing row' => [false];
    }

    /**
     * @dataProvider missingTokenProvider
     *
     * @param mixed $token
     */
    public function testNoWidgetWithoutMerchantToken($token)
    {
        $this->stored['scalapay_merchant_token'] = $token;

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function switchProvider()
    {
        yield 'product off' => ['product', 'scalapay_product_widget', '0'];

        yield 'cart off' => ['cart', 'scalapay_cart_widget', '0'];

        yield 'checkout off' => ['checkout', 'scalapay_checkout_widget', '0'];

        yield 'product row missing' => ['product', 'scalapay_product_widget', false];

        yield 'cart row missing' => ['cart', 'scalapay_cart_widget', false];

        yield 'checkout row missing' => ['checkout', 'scalapay_checkout_widget', false];
    }

    /**
     * @dataProvider switchProvider
     *
     * @param string $page
     * @param string $key
     * @param mixed $value
     */
    public function testThePageSwitchMustBeOn($page, $key, $value)
    {
        $this->stored[$key] = $value;

        $this->assertFalse($this->action->isWidgetAvailable($page));
    }

    public function testASwitchOnlyAffectsItsOwnPage()
    {
        $this->stored['scalapay_product_widget'] = '0';

        $this->assertTrue($this->action->isWidgetAvailable('cart'));
    }

    public function testNoWidgetInTheQuickview()
    {
        $this->request['action'] = 'quickview';

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function testOnlyEuroCartsGetTheWidget()
    {
        $this->context->currency->iso_code = 'USD';
        $this->apm_eligibility->shouldNotReceive('getApiLimits');

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    /**
     * @dataProvider pageProvider
     *
     * @param string $page
     */
    public function testNoWidgetWhenEuroIsNotAMerchantCurrency($page)
    {
        $this->stored['currencies'] = 'USD;GBP';

        $this->assertFalse($this->action->isWidgetAvailable($page));
        $this->assertFalse($this->action->isWidgetAvailable($page, 150.0));
    }

    /**
     * @dataProvider pageProvider
     *
     * @param string $page
     */
    public function testTheWidgetIsAvailableWhenEuroIsTheOnlyMerchantCurrency($page)
    {
        $this->stored['currencies'] = 'EUR';

        $this->assertTrue($this->action->isWidgetAvailable($page, 150.0));
    }

    public function testThePaymentMethodsAreLoadedOncePerRequest()
    {
        $this->payment_method_class->shouldReceive('getPaymentMethod')
            ->once()
            ->with('scalapay')
            ->andReturn($this->scalapay_payment_method);
        $other_action = \Mockery::mock(\PayPlug\src\actions\ScalapayWidgetAction::class)->makePartial();
        $other_action->dependencies = $this->dependencies;

        $this->assertTrue($this->action->isWidgetAvailable('product'));
        $this->assertTrue($this->action->isWidgetAvailable('product', 150.0));
        $this->assertTrue($other_action->isWidgetAvailable('checkout'));
    }

    public function testARefusedMerchantCurrencyIsAlsoKeptForTheRequest()
    {
        $this->stored['currencies'] = 'USD';
        $this->payment_method_class->shouldReceive('getPaymentMethod')
            ->once()
            ->with('scalapay')
            ->andReturn($this->scalapay_payment_method);

        $this->assertFalse($this->action->isWidgetAvailable('product'));
        $this->assertFalse($this->action->isWidgetAvailable('cart'));
    }

    public function testNoWidgetWhenTheScalapayPaymentMethodCannotBeLoaded()
    {
        $this->payment_method_class->shouldReceive('getPaymentMethod')->with('scalapay')->andReturn([]);

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function testThePaymentMethodsAreNotLoadedForANonEuroCart()
    {
        $this->context->currency->iso_code = 'USD';
        $this->payment_method_class->shouldNotReceive('getPaymentMethod');

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function testThePaymentMethodsAreNotLoadedWhenScalapayIsOff()
    {
        $this->stored['payment_methods'] = '{"scalapay":false}';
        $this->payment_method_class->shouldNotReceive('getPaymentMethod');

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function testNoWidgetWhenTheAccountLimitsAreNotKnown()
    {
        $this->apm_eligibility->shouldReceive('getApiLimits')->with('scalapay', 'EUR')->andReturn([
            'configured' => false,
            'currency_supported' => true,
            'min' => null,
            'max' => null,
            'countries' => null,
        ]);

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function testNoWidgetWhenTheAccountLimitsExcludeTheCurrency()
    {
        $this->apm_eligibility->shouldReceive('getApiLimits')->with('scalapay', 'EUR')->andReturn([
            'configured' => true,
            'currency_supported' => false,
            'min' => null,
            'max' => null,
            'countries' => null,
        ]);

        $this->assertFalse($this->action->isWidgetAvailable('product'));
    }

    public function amountProvider()
    {
        yield 'below the minimum' => [4.99, false];

        yield 'the minimum' => [5.0, true];

        yield 'the maximum' => [4000.0, true];

        yield 'above the maximum' => [4000.01, false];

        yield 'zero' => [0, false];
    }

    /**
     * @dataProvider amountProvider
     *
     * @param float $amount
     * @param bool $expected
     */
    public function testTheAmountMustBeWithinTheEffectiveLimits($amount, $expected)
    {
        $this->assertSame($expected, $this->action->isWidgetAvailable('product', $amount));
    }

    public function testTheAmountIsConvertedInTheCartCurrency()
    {
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(19.99, false, 'EUR')->andReturn(1999);

        $this->assertTrue($this->action->isWidgetAvailable('cart', 19.99));
    }

    public function testNoWidgetWhenTheMerchantLimitsAreInvalid()
    {
        $this->apm_eligibility->shouldReceive('getEffectiveLimits')->with('scalapay', 'EUR')->andReturn([
            'min' => 6000,
            'max' => 5000,
            'countries' => null,
            'valid' => false,
        ]);

        $this->assertFalse($this->action->isWidgetAvailable('product', 55.0));
    }

    public function testOpenLimitsAcceptAnyPositiveAmount()
    {
        $this->apm_eligibility->shouldReceive('getEffectiveLimits')->with('scalapay', 'EUR')->andReturn([
            'min' => null,
            'max' => null,
            'countries' => null,
            'valid' => true,
        ]);

        $this->assertTrue($this->action->isWidgetAvailable('product', 100000.0));
    }
}
