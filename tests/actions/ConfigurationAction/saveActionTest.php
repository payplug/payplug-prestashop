<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class saveActionTest extends BaseConfigurationAction
{
    public function setUp(): void
    {
        parent::setUp();

        $this->module->shouldReceive([
            'enable' => true,
        ]);
    }

    public function invalidObjectFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [true];

        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $datas
     */
    public function testWhenGivenDataIsInvalidFormat($datas)
    {
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenGivenActionIsEmpty()
    {
        $datas = new \stdClass();
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenGivenActionIsInvalid()
    {
        $datas = new \stdClass();
        $datas->action = 'test';
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenNoApplepayDisplayIsSelected()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->enable_applepay_cart = false;
        $datas->enable_applepay_checkout = false;
        $datas->enable_applepay_product = false;
        $datas->applepay_carriers = [];

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.applepay.display.text',
                    'close' => 'modal.applepay.display.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenApplepayCarriersIsEmpty()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->enable_applepay_cart = true;
        $datas->applepay_carriers = [];

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.applepay.carrier.text',
                    'close' => 'modal.applepay.carrier.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenConfigurationCannotBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->applepay_carriers = ['1', '2', '4'];
        $datas->enable_applepay_cart = true;
        $datas->enable_standard = 1;

        $this->configuration->shouldReceive([
            'updateValue' => false,
        ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'An error has occurred while register applepay_carriers',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenScalapayMinAmountIsGreaterThanMaxAmount()
    {
        $this->stubScalapaySave();

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.scalapay.thresholds.text',
                    'close' => 'modal.scalapay.thresholds.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($this->scalapaySaveData(2000, 10))
        );
    }

    public function testWhenScalapayAmountsWidenTheAccountRange()
    {
        $this->stubScalapaySave();

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.scalapay.thresholds.text',
                    'close' => 'modal.scalapay.thresholds.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($this->scalapaySaveData(1, 9000))
        );
    }

    public function testWhenScalapayAmountsNarrowTheAccountRangeTheyArePersisted()
    {
        $stored = &$this->stubScalapaySave();

        $this->assertTrue($this->action->saveAction($this->scalapaySaveData(10, 2000))['success']);
        $this->assertSame('EUR:1000', $stored['scalapay_custom_min_amounts']);
        $this->assertSame('EUR:200000', $stored['scalapay_custom_max_amounts']);
    }

    public function testWhenScalapayMinAmountIsSubmittedAloneAboveTheMaximumInForce()
    {
        // The merchant already capped Scalapay at 2000 EUR. A payload carrying the
        // minimum alone still has to be checked against that ceiling, or it would be
        // persisted above it and hide Scalapay for every cart.
        $stored = &$this->stubScalapaySave(['min' => 500, 'max' => 200000]);

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_scalapay = true;
        $datas->scalapay_min_amounts = 3000;

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.scalapay.thresholds.text',
                    'close' => 'modal.scalapay.thresholds.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
        $this->assertSame([], $stored);
    }

    public function testWhenScalapayMaxAmountIsSubmittedAloneBelowTheMinimumInForce()
    {
        // Mirror case: the merchant already raised the floor to 100 EUR, so a maximum
        // of 50 EUR submitted alone inverts the range even though it sits inside the
        // account's own authorized bounds.
        $stored = &$this->stubScalapaySave(['min' => 10000, 'max' => 400000]);

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_scalapay = true;
        $datas->scalapay_max_amounts = 50;

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.scalapay.thresholds.text',
                    'close' => 'modal.scalapay.thresholds.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
        $this->assertSame([], $stored);
    }

    public function testWhenScalapayMinAmountIsSubmittedAloneWithinTheRangeInForceItIsPersisted()
    {
        $stored = &$this->stubScalapaySave(['min' => 500, 'max' => 200000]);

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_scalapay = true;
        $datas->scalapay_min_amounts = 10;

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertSame('EUR:1000', $stored['scalapay_custom_min_amounts']);
        $this->assertArrayNotHasKey('scalapay_custom_max_amounts', $stored);
    }

    public function testWhenOneyAmountIsOutsideTheAccountRangeItIsNotPersisted()
    {
        $stored = &$this->stubOneySave();

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_oney = true;
        $datas->oney_min_amounts = 1;

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertArrayNotHasKey('oney_custom_min_amounts', $stored);
    }

    public function testWhenOneyAmountIsInsideTheAccountRangeItIsPersisted()
    {
        $stored = &$this->stubOneySave();

        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_oney = true;
        $datas->oney_min_amounts = 200;

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertSame('EUR:20000', $stored['oney_custom_min_amounts']);
    }

    public function testWhenConfigurationCanBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->applepay_carriers = ['1', '2', '4'];
        $datas->enable_applepay_cart = true;
        $datas->payplug_standard = 1;

        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);

        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }

    /**
     * @description Wire up what the Scalapay branch of saveAction() needs: the account's
     *              authorized range and the euro/cent conversion.
     *
     * @param array $limits_in_force The limits the merchant currently gets, i.e. the
     *                               account's range already narrowed by their own custom
     *                               amounts. Defaults to the account's own range, as for
     *                               a merchant who never narrowed anything.
     *
     * @return array the configuration keys saveAction() persisted, by key
     */
    private function &stubScalapaySave($limits_in_force = ['min' => 500, 'max' => 400000])
    {
        $scalapay = \Mockery::mock('ScalapayPaymentMethod');
        $scalapay->shouldReceive('getScalapayPriceLimit')
            ->with(false)
            ->andReturn([
                'min' => 500,
                'max' => 400000,
            ]);
        $scalapay->shouldReceive('getScalapayPriceLimit')
            ->withNoArgs()
            ->andReturn($limits_in_force);
        $scalapay->shouldReceive('setCustomScalapayLimit')
            ->andReturnUsing(function ($amount) {
                return 'EUR:' . (int) $amount;
            });

        $payment_method_class = \Mockery::mock('PaymentMethodClass');
        $payment_method_class->shouldReceive('getPaymentMethod')
            ->with('scalapay')
            ->andReturn($scalapay);
        $this->plugin->shouldReceive([
            'getPaymentMethodClass' => $payment_method_class,
        ]);

        $amount_helper = \Mockery::mock('AmountHelper');
        $amount_helper->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount) {
                return (int) round($amount * 100);
            });
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($amount_helper);

        $this->dependencies->shouldReceive([
            'getValidators' => [
                'payment' => \Mockery::mock(\PayPlug\src\utilities\validators\paymentValidator::class)->makePartial(),
            ],
        ]);

        $stored = [];
        $this->configuration_class->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) use (&$stored) {
                $stored[$key] = $value;

                return true;
            });

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);

        return $stored;
    }

    /**
     * @description Wire up what the Oney branch of saveAction() needs: the account's
     *              authorized range and the euro/cent conversion.
     *
     * @return array the configuration keys saveAction() persisted, by key
     */
    private function &stubOneySave()
    {
        $oney = \Mockery::mock('OneyPaymentMethod');
        $oney->shouldReceive('getOneyPriceLimit')
            ->with(false)
            ->andReturn([
                'min' => 10000,
                'max' => 300000,
            ]);
        $oney->shouldReceive('setCustomOneyLimit')
            ->andReturnUsing(function ($amount) {
                return 'EUR:' . (int) $amount;
            });

        $payment_method_class = \Mockery::mock('PaymentMethodClass');
        $payment_method_class->shouldReceive('getPaymentMethod')
            ->with('oney')
            ->andReturn($oney);
        $this->plugin->shouldReceive([
            'getPaymentMethodClass' => $payment_method_class,
        ]);

        $amount_helper = \Mockery::mock('AmountHelper');
        $amount_helper->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount) {
                return (int) round($amount * 100);
            });
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($amount_helper);

        $this->dependencies->shouldReceive([
            'getValidators' => [
                'payment' => \Mockery::mock(\PayPlug\src\utilities\validators\paymentValidator::class)->makePartial(),
            ],
        ]);

        $stored = [];
        $this->configuration_class->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) use (&$stored) {
                $stored[$key] = $value;

                return true;
            });

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);

        return $stored;
    }

    private function scalapaySaveData($min, $max)
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_scalapay = true;
        $datas->scalapay_min_amounts = $min;
        $datas->scalapay_max_amounts = $max;

        return $datas;
    }
}
