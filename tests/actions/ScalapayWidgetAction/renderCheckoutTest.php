<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class renderCheckoutTest extends BaseScalapayWidgetAction
{
    public function testTheCheckoutWidgetIsRendered()
    {
        $this->controller = 'order';

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCheckout());
        $this->assertSame('checkout', $this->assigned['scalapay_type']);
        $this->assertSame('150.00', $this->assigned['scalapay_amount']);
    }

    public function testTheAmountIsTheCartTotalWithTaxes()
    {
        $this->context->cart->shouldReceive('getOrderTotal')->once()->with(true)->andReturn(89.9);

        $this->action->renderCheckout();

        $this->assertSame('89.90', $this->assigned['scalapay_amount']);
    }

    public function testHummingbirdShowingPricesWithoutTaxesGetsTheTotalWithTaxes()
    {
        $this->context->shop->theme_name = 'hummingbird';
        $this->context->cart->shouldReceive('getOrderTotal')->with(false)->andReturn(100.0);
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn(120.0);

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCheckout());
        $this->assertSame('[]', $this->assigned['scalapay_amount_selectors']);
        $this->assertSame('120.00', $this->assigned['scalapay_amount']);
    }

    public function testTheAmountIsLeftOutWithoutACart()
    {
        $this->context->cart = null;

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCheckout());
        $this->assertSame('', $this->assigned['scalapay_amount']);
    }

    public function testTheCheckoutWidgetDoesNotDependOnTheController()
    {
        $this->controller = 'ps_onepagecheckout';

        $this->assertNotSame('', $this->action->renderCheckout());
    }

    public function testTheCheckoutWidgetIsNotGatedByTheAmount()
    {
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn(4500.0);
        $this->apm_eligibility->shouldNotReceive('getEffectiveLimits');

        $this->assertNotSame('', $this->action->renderCheckout());
        $this->assertSame('4500.00', $this->assigned['scalapay_amount']);
    }

    public function testNothingIsRenderedWhenTheCheckoutSwitchIsOff()
    {
        $this->stored['scalapay_checkout_widget'] = '0';

        $this->assertSame('', $this->action->renderCheckout());
    }

    public function testNothingIsRenderedWithoutMerchantToken()
    {
        $this->stored['scalapay_merchant_token'] = '';

        $this->assertSame('', $this->action->renderCheckout());
    }
}
