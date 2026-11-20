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
    }

    public function testTheAmountIsTheCartTotalWithTaxes()
    {
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(150.0, false, 'EUR')->andReturn(15000);

        $this->assertNotSame('', $this->action->renderCart());
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
}
