<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class saveActionScalapayWidgetTest extends BaseConfigurationAction
{
    private $stored;
    private $stored_selectors;

    public function setUp(): void
    {
        parent::setUp();

        $this->stored = [];
        $this->stored_selectors = '{}';
        $this->configuration_class->shouldReceive('getValue')
            ->with('scalapay_amount_selectors')
            ->andReturnUsing(function () {
                return $this->stored_selectors;
            });
        $this->configuration_class->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                $this->stored[$key] = $value;

                return true;
            })
            ->byDefault();

        $widget_action = \Mockery::mock('ScalapayWidgetAction');
        $widget_action->shouldReceive('getThemeAmountSelectors')->andReturnUsing(function ($page) {
            return 'product' === $page ? ['.current-price-value'] : ['.cart-summary-totals .cart-total .value'];
        });
        $this->module->shouldReceive('getService')
            ->with('payplug.action.scalapay_widget')
            ->andReturn($widget_action)
            ->byDefault();

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);
    }

    public function testTheWidgetSwitchesAreSavedWhenScalapayIsEnabled()
    {
        $datas = $this->getDatas(true);
        $datas->enable_scalapay_checkout_widget = true;
        $datas->enable_scalapay_product_widget = false;
        $datas->enable_scalapay_cart_widget = true;

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('1', $this->stored['scalapay_checkout_widget']);
        $this->assertSame('', $this->stored['scalapay_product_widget']);
        $this->assertSame('1', $this->stored['scalapay_cart_widget']);
    }

    public function testTheWidgetSwitchesAreNotSavedWhenScalapayIsDisabled()
    {
        $datas = $this->getDatas(false);
        $datas->enable_scalapay_checkout_widget = false;
        $datas->enable_scalapay_product_widget = false;
        $datas->enable_scalapay_cart_widget = false;
        $datas->scalapay_amount_selectors_product = '#price';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertArrayNotHasKey('scalapay_checkout_widget', $this->stored);
        $this->assertArrayNotHasKey('scalapay_product_widget', $this->stored);
        $this->assertArrayNotHasKey('scalapay_cart_widget', $this->stored);
        $this->assertArrayNotHasKey('scalapay_amount_selectors', $this->stored);
    }

    public function testFieldsAbsentFromThePayloadLeaveTheStoredValuesUntouched()
    {
        $this->assertTrue($this->action->saveAction($this->getDatas(true))['success']);

        foreach (['scalapay_checkout_widget', 'scalapay_product_widget', 'scalapay_cart_widget', 'scalapay_amount_selectors'] as $key) {
            $this->assertArrayNotHasKey($key, $this->stored);
        }
    }

    public function testTheSelectorsAreSavedAsOneJsonObject()
    {
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = ' #price , .price ';
        $datas->scalapay_amount_selectors_cart = '.total';
        $datas->scalapay_amount_selectors_checkout = '';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{"product":["#price",".price"],"cart":[".total"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function testTheThemeDefaultIsNotStoredAsCustomSelectors()
    {
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '.current-price-value';
        $datas->scalapay_amount_selectors_cart = ' .cart-summary-totals .cart-total .value ';
        $datas->scalapay_amount_selectors_checkout = '';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{}', $this->stored['scalapay_amount_selectors']);
    }

    public function testPostingTheThemeDefaultReplacesStoredCustomSelectors()
    {
        $this->stored_selectors = '{"product":["#old"],"cart":[".old"]}';
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '.current-price-value';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{"cart":[".old"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function testACustomValueNextToTheThemeDefaultIsStored()
    {
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '.current-price-value, #price';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{"product":[".current-price-value","#price"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function testSelectorsAreStoredAsPostedWhenTheWidgetActionIsMissing()
    {
        $this->module->shouldReceive('getService')->with('payplug.action.scalapay_widget')->andReturn(null);
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '.current-price-value';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{"product":[".current-price-value"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function testAMissingSelectorFieldKeepsTheStoredSelectorsOfItsPage()
    {
        $this->stored_selectors = '{"product":["#old"],"cart":[".old"]}';
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_cart = '.total';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{"product":["#old"],"cart":[".total"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function testAnEmptySelectorFieldGoesBackToTheThemeDefault()
    {
        $this->stored_selectors = '{"product":["#old"],"cart":[".old"]}';
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{"cart":[".old"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function testAMalformedStoredValueIsReplaced()
    {
        $this->stored_selectors = '{"product":';
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '#price';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('{"product":["#price"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function invalidSelectorProvider()
    {
        yield 'quote' => ['#price"'];

        yield 'markup' => ['<img src=x onerror=alert(1)>'];

        yield 'too long' => [str_repeat('a', 201)];

        yield 'too many' => [implode(',', array_fill(0, 11, '.a'))];

        yield 'not a string' => [['#price']];
    }

    /**
     * @dataProvider invalidSelectorProvider
     *
     * @param mixed $value
     */
    public function testInvalidSelectorsRefuseTheScalapaySettings($value)
    {
        $datas = $this->getDatas(true);
        $datas->enable_scalapay_product_widget = true;
        $datas->scalapay_amount_selectors_product = '#price';
        $datas->scalapay_amount_selectors_cart = $value;

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.scalapay.amount_selectors.text',
                    'close' => 'modal.error.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
        foreach (['scalapay_checkout_widget', 'scalapay_product_widget', 'scalapay_cart_widget', 'scalapay_amount_selectors'] as $key) {
            $this->assertArrayNotHasKey($key, $this->stored);
        }
    }

    public function testInvalidSelectorsDoNotPreventTheOtherSettingsFromBeingSaved()
    {
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_cart = '<b>';

        $this->assertFalse($this->action->saveAction($datas)['success']);
        $this->assertArrayHasKey('payment_methods', $this->stored);
    }

    public function testAnErrorIsReturnedWhenASwitchCannotBeStored()
    {
        $this->configuration_class->shouldReceive('set')
            ->with('scalapay_cart_widget', \Mockery::any())
            ->andReturn(false);
        $datas = $this->getDatas(true);
        $datas->enable_scalapay_cart_widget = true;

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testChildCombinatorsAreSaved()
    {
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '.prices > .price';

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertSame('{"product":[".prices > .price"]}', $this->stored['scalapay_amount_selectors']);
    }

    public function testAnErrorIsReturnedWhenTheSelectorsCannotBeStored()
    {
        $this->configuration_class->shouldReceive('set')
            ->with('scalapay_amount_selectors', \Mockery::any())
            ->andReturn(false);
        $datas = $this->getDatas(true);
        $datas->scalapay_amount_selectors_product = '#price';

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testTheOneyKeysAreUnaffected()
    {
        $datas = $this->getDatas(true);
        $datas->enable_scalapay_cart_widget = true;

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertArrayNotHasKey('oney_cart_cta', $this->stored);
        $this->assertArrayNotHasKey('oney_product_cta', $this->stored);
        $this->assertArrayNotHasKey('oney_optimized', $this->stored);
    }

    /**
     * @param bool $enable_scalapay
     *
     * @return \stdClass
     */
    private function getDatas($enable_scalapay)
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_standard = true;
        $datas->enable_scalapay = $enable_scalapay;

        return $datas;
    }
}
