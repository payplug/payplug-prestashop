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
    }

    public function testTheCheckoutWidgetDoesNotDependOnTheController()
    {
        $this->controller = 'ps_onepagecheckout';

        $this->assertNotSame('', $this->action->renderCheckout());
    }

    public function testTheCheckoutWidgetIsNotGatedByTheAmount()
    {
        $this->amount_helper->shouldNotReceive('convertAmount');

        $this->assertNotSame('', $this->action->renderCheckout());
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
