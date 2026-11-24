<?php

namespace PayPlug\tests\classes\HookClass;

/**
 * @group unit
 * @group class
 * @group hook_class
 */
class renderScalapayWidgetTest extends BaseHookClass
{
    const SERVICE = 'payplug.action.scalapay_widget';

    private $logs = [];
    private $widget_action;

    public function setUp(): void
    {
        parent::setUp();
        $this->logs = [];

        $this->widget_action = \Mockery::mock('ScalapayWidgetAction');
        $this->module->shouldReceive('getService')
            ->with(self::SERVICE)
            ->andReturn($this->widget_action)
            ->byDefault();

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')->andReturnUsing(function ($message, $level = 'info') {
            $this->logs[] = ['level' => $level, 'message' => $message];

            return true;
        });
        $this->plugin->shouldReceive('getLogger')->andReturn($logger);
    }

    public function testTheProductWidgetIsRenderedWithTheHookParameters()
    {
        $params = ['type' => 'after_price', 'product' => ['price_amount' => 30.0]];
        $this->widget_action->shouldReceive('renderProduct')->once()->with($params)->andReturn('<scalapay-widget>');

        $this->assertSame('<scalapay-widget>', $this->hook->renderScalapayWidget('product', $params));
    }

    public function testTheActionIsNotBuiltForTheOtherPriceBlocks()
    {
        $this->module->shouldNotReceive('getService');

        $this->assertSame('', $this->hook->renderScalapayWidget('product', ['type' => 'unit_price']));
        $this->assertSame('', $this->hook->renderScalapayWidget('product', []));
    }

    public function testTheCartWidgetIsRendered()
    {
        $this->controller = 'cart';
        $this->widget_action->shouldReceive('renderCart')->once()->andReturn('<scalapay-widget>');

        $this->assertSame('<scalapay-widget>', $this->hook->renderScalapayWidget('cart'));
    }

    public function testTheScriptIsRendered()
    {
        $this->widget_action->shouldReceive('renderScriptTag')->once()->andReturn('<script>');

        $this->assertSame('<script>', $this->hook->renderScalapayWidget('script'));
    }

    public function testAnUnknownPageRendersNothing()
    {
        $this->widget_action->shouldNotReceive('renderProduct');
        $this->widget_action->shouldNotReceive('renderCart');
        $this->widget_action->shouldNotReceive('renderScriptTag');

        $this->assertSame('', $this->hook->renderScalapayWidget('home'));
    }

    public function testTheActionIsBuiltOnce()
    {
        $this->module->shouldReceive('getService')->with(self::SERVICE)->once()->andReturn($this->widget_action);
        $this->widget_action->shouldReceive('renderScriptTag')->andReturn('<script>');
        $this->widget_action->shouldReceive('renderProduct')->andReturn('<scalapay-widget>');

        $this->assertSame('<script>', $this->hook->renderScalapayWidget('script'));
        $this->assertSame('<scalapay-widget>', $this->hook->renderScalapayWidget('product', ['type' => 'after_price']));
    }

    public function testARenderingErrorIsLoggedAndRendersNothing()
    {
        $this->controller = 'cart';
        $this->widget_action->shouldReceive('renderCart')->andThrow(new \RuntimeException('template missing'));

        $this->assertSame('', $this->hook->renderScalapayWidget('cart'));
        $this->assertCount(1, $this->logs);
        $this->assertSame('error', $this->logs[0]['level']);
        $this->assertStringContainsString('template missing', $this->logs[0]['message']);
    }

    public function testAMissingServiceIsLoggedAndRendersNothing()
    {
        $this->module->shouldReceive('getService')->with(self::SERVICE)->andReturn(null);

        $this->assertSame('', $this->hook->renderScalapayWidget('product', ['type' => 'after_price']));
        $this->assertCount(1, $this->logs);
        $this->assertSame('error', $this->logs[0]['level']);
    }

    public function testAnActionThatCannotBeBuiltIsLoggedAndRendersNothing()
    {
        $this->module->shouldReceive('getService')->with(self::SERVICE)->andThrow(new \Error('container broken'));

        $this->assertSame('', $this->hook->renderScalapayWidget('script'));
        $this->assertStringContainsString('container broken', $this->logs[0]['message']);
    }

    public function testFalseFromTheActionRendersNothing()
    {
        $this->controller = 'cart';
        $this->widget_action->shouldReceive('renderCart')->andReturn(false);

        $this->assertSame('', $this->hook->renderScalapayWidget('cart'));
    }

    public function pageProvider()
    {
        yield ['product', 'product', ['type' => 'after_price']];

        yield ['cart', 'cart', []];

        yield ['script', 'order', []];
    }

    /**
     * @dataProvider pageProvider
     *
     * @param string $page
     * @param string $controller
     * @param array $params
     */
    public function testTheActionIsNotBuiltWhenScalapayIsOff($page, $controller, $params)
    {
        $this->controller = $controller;
        $this->configuration_values['payment_methods'] = '{"scalapay":false}';
        $this->module->shouldNotReceive('getService')->with(self::SERVICE);

        $this->assertSame('', $this->hook->renderScalapayWidget($page, $params));
    }

    public function testTheActionIsNotBuiltWithoutPaymentMethods()
    {
        $this->configuration_values['payment_methods'] = false;
        $this->module->shouldNotReceive('getService')->with(self::SERVICE);

        $this->assertSame('', $this->hook->renderScalapayWidget('script'));
    }

    public function otherControllerProvider()
    {
        yield 'product widget on the cart page' => ['product', 'cart', ['type' => 'after_price']];

        yield 'product widget on a category' => ['product', 'category', ['type' => 'after_price']];

        yield 'cart widget on the product page' => ['cart', 'product', []];

        yield 'script on the home page' => ['script', 'index', []];
    }

    /**
     * @dataProvider otherControllerProvider
     *
     * @param string $page
     * @param string $controller
     * @param array $params
     */
    public function testTheActionIsNotBuiltOnOtherPages($page, $controller, $params)
    {
        $this->controller = $controller;
        $this->module->shouldNotReceive('getService')->with(self::SERVICE);

        $this->assertSame('', $this->hook->renderScalapayWidget($page, $params));
    }

    public function testTheScriptIsRenderedOnTheCheckout()
    {
        $this->controller = 'order';
        $this->widget_action->shouldReceive('renderScriptTag')->once()->andReturn('<script>');

        $this->assertSame('<script>', $this->hook->renderScalapayWidget('script'));
    }
}
