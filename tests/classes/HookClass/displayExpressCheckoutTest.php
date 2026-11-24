<?php

namespace PayPlug\tests\classes\HookClass;

use PayPlug\classes\HookClass;

/**
 * @group unit
 * @group class
 * @group hook_class
 */
class displayExpressCheckoutTest extends BaseHookClass
{
    private $cart_action;
    private $oney_action;
    private $widget_action;

    public function setUp(): void
    {
        parent::setUp();

        $this->controller = 'cart';
        $this->configuration_values['oney_cart_cta'] = '1';

        $this->cart_action = \Mockery::mock('CartAction');
        $this->cart_action->shouldReceive('renderPaymentCTA')->andReturn('<applepay-cta>')->byDefault();
        $this->oney_action = \Mockery::mock('OneyAction');
        $this->oney_action->shouldReceive('renderCTA')->withNoArgs()->andReturn('<oney-cta>')->byDefault();
        $this->widget_action = \Mockery::mock('ScalapayWidgetAction');
        $this->module->shouldReceive('getService')
            ->with('payplug.action.scalapay_widget')
            ->andReturn($this->widget_action)
            ->byDefault();
        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')->andReturn(true);
        $this->plugin->shouldReceive([
            'getCartAction' => $this->cart_action,
            'getLogger' => $logger,
            'getOneyAction' => $this->oney_action,
        ]);

        $this->hook = new HookClass($this->dependencies);
    }

    public function testTheScalapayWidgetIsAddedAfterTheApplePayAndOneyCallsToAction()
    {
        $this->widget_action->shouldReceive('renderCart')->once()->andReturn('<scalapay-widget>');

        $this->assertSame('<applepay-cta><oney-cta><scalapay-widget>', $this->hook->displayExpressCheckout());
    }

    public function testTheScalapayWidgetIsShownWhenTheOneyCallToActionIsOff()
    {
        $this->configuration_values['oney_cart_cta'] = '0';
        $this->oney_action->shouldNotReceive('renderCTA');
        $this->widget_action->shouldReceive('renderCart')->andReturn('<scalapay-widget>');

        $this->assertSame('<applepay-cta><scalapay-widget>', $this->hook->displayExpressCheckout());
    }

    public function testTheOtherCallsToActionAreKeptWhenTheScalapayWidgetFails()
    {
        $this->widget_action->shouldReceive('renderCart')->andThrow(new \RuntimeException('broken'));

        $this->assertSame('<applepay-cta><oney-cta>', $this->hook->displayExpressCheckout());
    }

    public function testRefusedCallsToActionAreKeptEmpty()
    {
        $this->cart_action->shouldReceive('renderPaymentCTA')->andReturn(false);
        $this->oney_action->shouldReceive('renderCTA')->andReturn(false);
        $this->widget_action->shouldReceive('renderCart')->andReturn('');

        $this->assertSame('', $this->hook->displayExpressCheckout());
    }

    public function testTheWidgetActionIsNotBuiltWhenScalapayIsOff()
    {
        $this->configuration_values['payment_methods'] = '{"scalapay":false}';
        $this->module->shouldNotReceive('getService')->with('payplug.action.scalapay_widget');

        $this->assertSame('<applepay-cta><oney-cta>', $this->hook->displayExpressCheckout());
    }
}
