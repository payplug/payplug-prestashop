<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @runTestsInSeparateProcesses
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

    public function testWhenApplepayCarriersIsEmpty()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_applepay_cart = true;
        $datas->applepay_carriers = [];

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.applepay.text',
                    'close' => 'modal.applepay.submit',
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
                    'message' => 'An error has occurred while register payplug_applepay_carriers',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenConfigurationCanBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
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
