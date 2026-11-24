<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

use PayPlug\src\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class renderScriptTagTest extends BaseScalapayWidgetAction
{
    public function testTheScriptUrlIsTheVersionFiveWidget()
    {
        $this->assertSame('https://cdn.scalapay.com/widget/v5/js/scalapay-widget.js', ScalapayWidgetAction::WIDGET_SCRIPT_URL);
    }

    public function pageProvider()
    {
        yield ['product'];

        yield ['cart'];

        yield ['order'];
    }

    /**
     * @dataProvider pageProvider
     *
     * @param string $controller
     */
    public function testTheScriptIsRenderedOnThePagesShowingAWidget($controller)
    {
        $this->controller = $controller;

        $this->assertSame('<scalapay/script.tpl>', $this->action->renderScriptTag());
        $this->assertSame(
            ['scalapay_widget_script_url' => 'https://cdn.scalapay.com/widget/v5/js/scalapay-widget.js'],
            $this->assigned
        );
    }

    public function testTheScriptIsRenderedOncePerRequest()
    {
        $this->assertNotSame('', $this->action->renderScriptTag());
        $this->assertSame('', $this->action->renderScriptTag());
    }

    public function otherPageProvider()
    {
        yield ['index'];

        yield ['category'];

        yield ['order-confirmation'];

        yield [''];
    }

    /**
     * @dataProvider otherPageProvider
     *
     * @param string $controller
     */
    public function testNoScriptOnTheOtherPages($controller)
    {
        $this->controller = $controller;

        $this->assertSame('', $this->action->renderScriptTag());
    }

    public function testNoScriptWithoutMerchantToken()
    {
        $this->stored['scalapay_merchant_token'] = '';

        $this->assertSame('', $this->action->renderScriptTag());
    }

    public function switchProvider()
    {
        yield ['product', 'scalapay_product_widget'];

        yield ['cart', 'scalapay_cart_widget'];

        yield ['order', 'scalapay_checkout_widget'];
    }

    /**
     * @dataProvider switchProvider
     *
     * @param string $controller
     * @param string $key
     */
    public function testNoScriptWhenTheWidgetOfThePageIsOff($controller, $key)
    {
        $this->controller = $controller;
        $this->stored[$key] = '0';

        $this->assertSame('', $this->action->renderScriptTag());
    }

    public function testTheScriptDoesNotDependOnTheAmount()
    {
        $this->controller = 'cart';
        $this->amount_helper->shouldNotReceive('convertAmount');

        $this->assertNotSame('', $this->action->renderScriptTag());
    }

    public function testNoScriptWhenEuroIsNotAMerchantCurrency()
    {
        $this->controller = 'cart';
        $this->stored['currencies'] = 'USD';

        $this->assertSame('', $this->action->renderScriptTag());
    }
}
