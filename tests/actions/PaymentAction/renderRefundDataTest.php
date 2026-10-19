<?php

namespace PayPlug\tests\actions\PaymentAction;

/**
 * @group unit
 * @group action
 * @group payment_action
 */
class renderRefundDataTest extends BasePaymentAction
{
    public $amount_refunded_payplug;
    public $amount_available;

    public function setUp(): void
    {
        parent::setUp();

        $this->amount_refunded_payplug = 42;
        $this->amount_available = 4242;
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount_refunded_payplug
     */
    public function testWhenGivenAmountRefundedPayplugIsntValidInteger($amount_refunded_payplug)
    {
        $this->assertSame(
            '',
            $this->action->renderRefundData($amount_refunded_payplug, $this->amount_available)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount_available
     */
    public function testWhenGivenAmountAvailableIsntValidInteger($amount_available)
    {
        $this->assertSame(
            '',
            $this->action->renderRefundData($this->amount_refunded_payplug, $amount_available)
        );
    }

    public function testAmountsAreConvertedInTheCurrencyTheyAreDisplayedIn()
    {
        $context = $this->context->get();
        $context->currency->iso_code = 'JPY';
        $context->smarty = \Mockery::mock('Smarty');
        $context->smarty->shouldReceive('assign');
        $this->configClass->shouldReceive('fetchTemplate')->andReturn('refund_data');
        $price_adapter = \Mockery::mock('PriceAdapter');
        $price_adapter->shouldReceive('formatPrice')->andReturn('');
        $amount_helper = \Mockery::mock('AmountHelper');
        $amount_helper->shouldReceive('convertAmount')
            ->with(42, true, 'JPY')
            ->once()
            ->andReturn(42.0);
        $amount_helper->shouldReceive('convertAmount')
            ->with(4242, true, 'JPY')
            ->once()
            ->andReturn(4242.0);
        $this->module->shouldReceive('getService')
            ->with('payplug.application.adapter.price')
            ->andReturn($price_adapter);
        $this->module->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($amount_helper);

        $this->assertSame(
            'refund_data',
            $this->action->renderRefundData($this->amount_refunded_payplug, $this->amount_available)
        );
    }

    public function testGivenCurrencyIsUsedInsteadOfTheBackOfficeCurrency()
    {
        $context = $this->context->get();
        $context->currency->iso_code = 'EUR';
        $context->smarty = \Mockery::mock('Smarty');
        $context->smarty->shouldReceive('assign');
        $this->configClass->shouldReceive('fetchTemplate')->andReturn('refund_data');
        $price_adapter = \Mockery::mock('PriceAdapter');
        $price_adapter->shouldReceive('formatPrice')->andReturn('');
        $amount_helper = \Mockery::mock('AmountHelper');
        $amount_helper->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount) {
                return (float) $amount;
            });
        $this->module->shouldReceive('getService')
            ->with('payplug.application.adapter.price')
            ->andReturn($price_adapter);
        $this->module->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($amount_helper);

        $this->assertSame(
            'refund_data',
            $this->action->renderRefundData($this->amount_refunded_payplug, $this->amount_available, 'JPY')
        );
        $amount_helper->shouldHaveReceived('convertAmount')->with(42, true, 'JPY')->once();
        $amount_helper->shouldHaveReceived('convertAmount')->with(4242, true, 'JPY')->once();
        $price_adapter->shouldHaveReceived('formatPrice')->with(42.0, 'JPY')->once();
        $price_adapter->shouldHaveReceived('formatPrice')->with(4242.0, 'JPY')->once();
        $amount_helper->shouldNotHaveReceived('convertAmount', [\Mockery::any(), true, 'EUR']);
        $price_adapter->shouldNotHaveReceived('formatPrice', [\Mockery::any(), 'EUR']);
    }

    /**
     * @dataProvider invalidCurrencyProvider
     *
     * @param mixed $currency
     */
    public function testWhenGivenCurrencyIsntValidString($currency)
    {
        $this->assertSame(
            '',
            $this->action->renderRefundData($this->amount_refunded_payplug, $this->amount_available, $currency)
        );
        $this->logger->shouldHaveReceived('addLog')
            ->with('PaymentAction::renderRefundData - Invalid argument, $currency must be a string.', 'error')
            ->once();
    }

    public function invalidCurrencyProvider()
    {
        return [
            'integer' => [42],
            'array' => [['JPY']],
            'boolean' => [false],
        ];
    }
}
