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

    public function testTheAmountIsThePriceTimesTheWantedQuantity()
    {
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(60.0, false, 'EUR')->andReturn(6000);

        $this->assertNotSame('', $this->action->renderProduct($this->params));
    }

    public function testTheProductQuantityIsUsedWhenTheRequestHasNone()
    {
        unset($this->request['quantity_wanted']);
        $this->params['product']['quantity_wanted'] = 3;
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(90.0, false, 'EUR')->andReturn(9000);

        $this->assertNotSame('', $this->action->renderProduct($this->params));
    }

    public function testOneUnitIsUsedWhenNoQuantityIsKnown()
    {
        unset($this->request['quantity_wanted']);
        $this->amount_helper->shouldReceive('convertAmount')->once()->with(30.0, false, 'EUR')->andReturn(3000);

        $this->assertNotSame('', $this->action->renderProduct($this->params));
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
        $this->stored['scalapay_amount_selectors'] = '{"product":["#price > span"]}';

        $this->action->renderProduct($this->params);

        $this->assertSame('tok"<x>', $this->assigned['scalapay_merchant_token']);
        $this->assertSame('["#price > span"]', $this->assigned['scalapay_amount_selectors']);
    }
}
