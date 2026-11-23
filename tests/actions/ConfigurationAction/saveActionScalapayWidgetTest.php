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

    public function setUp(): void
    {
        parent::setUp();

        $this->stored = [];
        $this->configuration_class->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                $this->stored[$key] = $value;

                return true;
            })
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

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertArrayNotHasKey('scalapay_checkout_widget', $this->stored);
        $this->assertArrayNotHasKey('scalapay_product_widget', $this->stored);
        $this->assertArrayNotHasKey('scalapay_cart_widget', $this->stored);
    }

    public function testFieldsAbsentFromThePayloadLeaveTheStoredValuesUntouched()
    {
        $this->assertTrue($this->action->saveAction($this->getDatas(true))['success']);

        foreach (['scalapay_checkout_widget', 'scalapay_product_widget', 'scalapay_cart_widget'] as $key) {
            $this->assertArrayNotHasKey($key, $this->stored);
        }
    }

    public function testPostedPriceSelectorsAreNotStored()
    {
        $datas = $this->getDatas(true);
        $datas->enable_scalapay_product_widget = true;
        $datas->scalapay_amount_selectors_product = '#price';
        $datas->scalapay_amount_selectors_cart = '<b>';

        $this->assertTrue($this->action->saveAction($datas)['success']);

        $this->assertSame('1', $this->stored['scalapay_product_widget']);
        $this->assertSame(
            [],
            preg_grep('/selector/', array_keys($this->stored))
        );
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
