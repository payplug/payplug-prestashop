<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class renderProductTest extends BaseScalapayWidgetAction
{
    private $params;

    public function setUp(): void
    {
        parent::setUp();

        $this->params = [
            'type' => 'after_price',
            'product' => [
                'id_product' => 1,
                'price_amount' => 30.0,
            ],
        ];
        $this->request['id_product'] = '1';
        $this->request['quantity_wanted'] = '2';
    }

    public function testTheProductWidgetIsRenderedAfterThePrice()
    {
        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderProduct($this->params));
        $this->assertSame(
            [
                'scalapay_merchant_token' => 'tok_test_123',
                'scalapay_type' => 'product',
                'scalapay_amount_selectors' => '[".current-price-value"]',
                'scalapay_amount' => '30.00',
                'scalapay_locale' => 'fr',
            ],
            $this->assigned
        );
    }

    public function otherTypeProvider()
    {
        yield ['old_price'];

        yield ['price'];

        yield ['custom_price'];

        yield ['unit_price'];

        yield ['weight'];
    }

    /**
     * @dataProvider otherTypeProvider
     *
     * @param string $type
     */
    public function testNothingIsRenderedForTheOtherPriceBlocks($type)
    {
        $this->params['type'] = $type;

        $this->assertSame('', $this->action->renderProduct($this->params));
    }

    public function testNothingIsRenderedWithoutAType()
    {
        unset($this->params['type']);

        $this->assertSame('', $this->action->renderProduct($this->params));
        $this->assertSame('', $this->action->renderProduct('after_price'));
    }

    public function testNothingIsRenderedOutsideTheProductPage()
    {
        $this->controller = 'category';

        $this->assertSame('', $this->action->renderProduct($this->params));
    }

    public function testTheWidgetIsRenderedOncePerRequest()
    {
        $this->assertNotSame('', $this->action->renderProduct($this->params));
        $this->assertSame('', $this->action->renderProduct($this->params));
    }

    public function testTheEligibilityAmountIsThePriceTimesTheWantedQuantity()
    {
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(60.0, false, 'EUR')->andReturn(6000);
        $this->amount_helper->shouldReceive('convertAmount')->with(30.0, false, 'EUR')->andReturn(3000);

        $this->assertNotSame('', $this->action->renderProduct($this->params));
    }

    public function testTheProductQuantityIsUsedWhenTheRequestHasNone()
    {
        unset($this->request['quantity_wanted']);
        $this->params['product']['quantity_wanted'] = 3;
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(90.0, false, 'EUR')->andReturn(9000);
        $this->amount_helper->shouldReceive('convertAmount')->with(30.0, false, 'EUR')->andReturn(3000);

        $this->assertNotSame('', $this->action->renderProduct($this->params));
    }

    public function testOneUnitIsUsedWhenNoQuantityIsKnown()
    {
        unset($this->request['quantity_wanted']);
        $this->amount_helper->shouldReceive('convertAmount')->twice()->with(30.0, false, 'EUR')->andReturn(3000);

        $this->assertNotSame('', $this->action->renderProduct($this->params));
    }

    public function testTheWidgetAmountIsTheDisplayedUnitPriceWhateverTheQuantity()
    {
        $this->request['quantity_wanted'] = '3';
        $this->params['product']['price_amount'] = 24.9;

        $this->action->renderProduct($this->params);

        $this->assertSame('24.90', $this->assigned['scalapay_amount']);
    }

    public function testTheWidgetAmountFollowsTheCombinationPrice()
    {
        $this->params['product']['id_product_attribute'] = 7;
        $this->params['product']['price_amount'] = '119.99';

        $this->action->renderProduct($this->params);

        $this->assertSame('119.99', $this->assigned['scalapay_amount']);
    }

    public function testTheWidgetAmountIsConvertedWithTheCartCurrency()
    {
        $this->amount_helper->shouldReceive('convertAmount')->with(60.0, false, 'EUR')->andReturn(6000);
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(30.0, false, 'EUR')->andReturn(3000);

        $this->action->renderProduct($this->params);

        $this->assertSame('30.00', $this->assigned['scalapay_amount']);
    }

    public function testTheProductCanBeAnArrayAccessObject()
    {
        $this->params['product'] = new \ArrayObject(['id_product' => 1, 'price_amount' => 30.0]);

        $this->assertNotSame('', $this->action->renderProduct($this->params));
    }

    public function testNothingIsRenderedWhenThePriceIsOutOfTheScalapayRange()
    {
        $this->params['product']['price_amount'] = 2.0;
        $this->request['quantity_wanted'] = '1';

        $this->assertSame('', $this->action->renderProduct($this->params));
        $this->assertSame([], $this->assigned);
    }

    public function testNothingIsRenderedWhenTheProductSwitchIsOff()
    {
        $this->stored['scalapay_product_widget'] = '0';

        $this->assertSame('', $this->action->renderProduct($this->params));
    }

    public function testAnotherProductOfThePageGetsNoWidget()
    {
        $this->params['product']['id_product'] = 2;

        $this->assertSame('', $this->action->renderProduct($this->params));
        $this->assertSame([], $this->assigned);
    }

    public function testARelatedProductGetsNoWidgetWhenThePageProductIsHidden()
    {
        $this->params['product']['price_amount'] = 2.0;
        $this->request['quantity_wanted'] = '1';
        $this->assertSame('', $this->action->renderProduct($this->params));

        $related = $this->params;
        $related['product'] = ['id_product' => 2, 'price_amount' => 30.0];
        $this->assertSame('', $this->action->renderProduct($related));
        $this->assertSame([], $this->assigned);
    }

    public function testNothingIsRenderedWithoutAProductId()
    {
        unset($this->params['product']['id_product']);

        $this->assertSame('', $this->action->renderProduct($this->params));
    }

    public function testNothingIsRenderedWithoutAProductInTheRequest()
    {
        unset($this->request['id_product']);

        $this->assertSame('', $this->action->renderProduct($this->params));
    }

    public function testTheTokenAndSelectorsAreAssignedRawForTheTemplateToEscape()
    {
        $this->stored['scalapay_merchant_token'] = 'tok"<x>';
        $this->context->shop->theme_name = 'hummingbird';

        $this->action->renderProduct($this->params);

        $this->assertSame('tok"<x>', $this->assigned['scalapay_merchant_token']);
        $this->assertSame('[".product__price"]', $this->assigned['scalapay_amount_selectors']);
    }

    public function testACustomThemeGetsNoSelectorButStillTheAmount()
    {
        $this->context->shop->theme_name = 'my_theme';

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderProduct($this->params));
        $this->assertSame('[]', $this->assigned['scalapay_amount_selectors']);
        $this->assertSame('30.00', $this->assigned['scalapay_amount']);
    }
}
