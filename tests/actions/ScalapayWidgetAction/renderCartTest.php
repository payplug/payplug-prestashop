<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class renderCartTest extends BaseScalapayWidgetAction
{
    public function setUp(): void
    {
        parent::setUp();

        $this->controller = 'cart';
    }

    public function testTheCartWidgetIsRendered()
    {
        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCart());
        $this->assertSame('cart', $this->assigned['scalapay_type']);
        $this->assertSame('[".cart-summary-totals .cart-total .value"]', $this->assigned['scalapay_amount_selectors']);
        $this->assertSame('150.00', $this->assigned['scalapay_amount']);
    }

    public function testTheAmountIsTheCartTotalWithTaxes()
    {
        $this->context->cart->shouldReceive('getOrderTotal')->once()->with(true)->andReturn(1234.5);
        $this->amount_helper->shouldReceive('convertAmount')->with(1234.5, false, 'EUR')->andReturn(123450);

        $this->assertNotSame('', $this->action->renderCart());
        $this->assertSame('1234.50', $this->assigned['scalapay_amount']);
    }

    public function testHummingbirdShowingPricesWithoutTaxesGetsTheTotalWithTaxes()
    {
        $this->context->shop->theme_name = 'hummingbird';
        $this->context->cart->shouldReceive('getOrderTotal')->with(false)->andReturn(100.0);
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn(120.0);

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCart());
        $this->assertSame('[]', $this->assigned['scalapay_amount_selectors']);
        $this->assertSame('120.00', $this->assigned['scalapay_amount']);
    }

    public function testACustomThemeGetsNoSelectorButStillTheAmount()
    {
        $this->context->shop->theme_name = 'my_theme';

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCart());
        $this->assertSame('[]', $this->assigned['scalapay_amount_selectors']);
        $this->assertSame('150.00', $this->assigned['scalapay_amount']);
    }

    public function testNothingIsRenderedOutsideTheCartPage()
    {
        $this->controller = 'order';

        $this->assertSame('', $this->action->renderCart());
    }

    public function testNothingIsRenderedWhenTheCartSwitchIsOff()
    {
        $this->stored['scalapay_cart_widget'] = '0';

        $this->assertSame('', $this->action->renderCart());
    }

    public function testNothingIsRenderedWhenTheCartIsOutOfTheScalapayRange()
    {
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn(4500.0);

        $this->assertSame('', $this->action->renderCart());
    }

    public function testNothingIsRenderedWithoutACart()
    {
        $this->context->cart = null;

        $this->assertSame('', $this->action->renderCart());
    }

    public function testNothingIsRenderedWhenEuroIsNotAMerchantCurrency()
    {
        $this->stored['currencies'] = 'USD';

        $this->assertSame('', $this->action->renderCart());
        $this->assertSame([], $this->assigned);
    }

    public function testNoEnvironmentIsAssignedByDefault()
    {
        $this->action->renderCart();

        $this->assertSame('', $this->assigned['scalapay_environment']);
    }

    public function testTheIntegrationEnvironmentIsAssignedWhenConfigured()
    {
        $this->widget_environment = 'integration';

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCart());
        $this->assertSame('integration', $this->assigned['scalapay_environment']);
    }
}
