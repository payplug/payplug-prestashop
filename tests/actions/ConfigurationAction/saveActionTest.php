<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class saveActionTest extends BaseConfigurationAction
{
    public function setUp()
    {
        parent::setUp();

        $module = \Mockery::mock('PrestashopModule');
        $module
            ->shouldReceive([
                'enable' => true,
            ]);

        $this->module = \Mockery::mock('Module');
        $this->module
            ->shouldReceive([
                'getInstanceByName' => $module,
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
        $datas->payplug_applepay_display = new \stdClass();
        $datas->payplug_applepay_display->cart = false;
        $datas->payplug_applepay_display->checkout = false;
        $datas->payplug_applepay_display->product = false;
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
        $datas->payplug_applepay_display = new \stdClass();
        $datas->payplug_applepay_display->cart = true;
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
        $datas->payplug_applepay_display = new \stdClass();
        $datas->payplug_applepay_display->cart = true;
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

    public function testWhenConfigurationCanBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay = true;
        $datas->applepay_carriers = ['1', '2', '4'];
        $datas->payplug_applepay_display = new \stdClass();
        $datas->payplug_applepay_display->cart = true;
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

        $telemetry = \Mockery::mock('MerchantTelemetryAction');
        $telemetry->shouldReceive([
            'sendAction' => [
                'result' => true,
            ],
        ]);
        $this->plugin->shouldReceive([
            'getMerchantTelemetryAction' => $telemetry,
        ]);

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }
}
