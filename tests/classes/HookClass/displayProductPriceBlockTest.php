<?php

namespace PayPlug\tests\classes\HookClass;

use PayPlug\classes\HookClass;

/**
 * @group unit
 * @group class
 * @group hook_class
 */
class displayProductPriceBlockTest extends BaseHookClass
{
    private $oney_action;
    private $params;
    private $widget_action;

    public function setUp(): void
    {
        parent::setUp();

        $this->params = ['type' => 'after_price', 'product' => ['id_product' => 1, 'price_amount' => 30.0]];
        $this->configuration_values['oney_product_cta'] = '1';

        $this->oney_action = \Mockery::mock('OneyAction');
        $this->widget_action = \Mockery::mock('ScalapayWidgetAction');
        $this->module->shouldReceive('getService')
            ->with('payplug.action.scalapay_widget')
            ->andReturn($this->widget_action)
            ->byDefault();
        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')->andReturn(true);
        $this->plugin->shouldReceive([
            'getLogger' => $logger,
            'getOneyAction' => $this->oney_action,
        ]);

        $this->hook = new HookClass($this->dependencies);
    }

    public function testTheScalapayWidgetIsAddedAfterTheOneyCallToAction()
    {
        $this->oney_action->shouldReceive('renderCTA')->once()->with($this->params)->andReturn('<oney-cta>');
        $this->widget_action->shouldReceive('renderProduct')->once()->with($this->params)->andReturn('<scalapay-widget>');

        $this->assertSame('<oney-cta><scalapay-widget>', $this->hook->displayProductPriceBlock($this->params));
    }

    public function testOnlyTheOneyCallToActionIsShownWithoutScalapayWidget()
    {
        $this->oney_action->shouldReceive('renderCTA')->andReturn('<oney-cta>');
        $this->widget_action->shouldReceive('renderProduct')->andReturn('');

        $this->assertSame('<oney-cta>', $this->hook->displayProductPriceBlock($this->params));
    }

    public function testOnlyTheScalapayWidgetIsShownWhenTheOneyCallToActionIsOff()
    {
        $this->configuration_values['oney_product_cta'] = '0';
        $this->oney_action->shouldNotReceive('renderCTA');
        $this->widget_action->shouldReceive('renderProduct')->andReturn('<scalapay-widget>');

        $this->assertSame('<scalapay-widget>', $this->hook->displayProductPriceBlock($this->params));
    }

    public function testNothingIsShownWhenBothAreOff()
    {
        $this->configuration_values['oney_product_cta'] = '0';
        $this->configuration_values['payment_methods'] = '{"scalapay":false}';
        $this->oney_action->shouldNotReceive('renderCTA');
        $this->module->shouldNotReceive('getService')->with('payplug.action.scalapay_widget');

        $this->assertSame('', $this->hook->displayProductPriceBlock($this->params));
    }

    public function testTheOneyOutputIsKeptWhenTheScalapayWidgetFails()
    {
        $this->oney_action->shouldReceive('renderCTA')->andReturn('<oney-cta>');
        $this->widget_action->shouldReceive('renderProduct')->andThrow(new \RuntimeException('broken'));

        $this->assertSame('<oney-cta>', $this->hook->displayProductPriceBlock($this->params));
    }

    public function testAnOneyCallToActionRefusalIsKeptEmpty()
    {
        $this->oney_action->shouldReceive('renderCTA')->andReturn(false);
        $this->widget_action->shouldReceive('renderProduct')->andReturn('<scalapay-widget>');

        $this->assertSame('<scalapay-widget>', $this->hook->displayProductPriceBlock($this->params));
    }

    public function testTheOneySwitchIsReadOncePerRequest()
    {
        $this->oney_action->shouldReceive('renderCTA')->twice()->andReturn('<oney-cta>');
        $this->widget_action->shouldReceive('renderProduct')->andReturn('');

        $this->hook->displayProductPriceBlock($this->params);
        $this->configuration_values['oney_product_cta'] = '0';

        $this->assertSame('<oney-cta>', $this->hook->displayProductPriceBlock(['type' => 'price']));
    }
}
