<?php

namespace PayPlug\tests\actions\MerchantTelemetryAction;

/**
 * @group unit
 * @group action
 * @group merchant_telemetry_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class sendActionTest extends BaseMerchantTelemetryAction
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $source
     */
    public function testWhenGivenSourceIsntValidString($source)
    {
        $this->assertFalse($this->action->sendAction($source));
    }

    public function testWhenMerchantTelemetryCannotBeRended()
    {
        $source = 'source';
        $this->action->shouldReceive([
            'renderTelemetries' => [
                'result' => false,
            ],
        ]);
        $this->assertFalse($this->action->sendAction($source));
    }

    public function testWhenMerchantTelemetryHasntChange()
    {
        $source = 'source';
        $this->action->shouldReceive([
            'renderTelemetries' => [
                'result' => true,
            ],
        ]);
        $this->assertTrue($this->action->sendAction($source));
    }

    public function testWhenMerchantTelemetryCannotBeSend()
    {
        $source = 'source';
        $this->action->shouldReceive([
            'renderTelemetries' => [
                'result' => true,
                'telemetries' => [
                    'version' => '4.x.x',
                    'name' => 'payplug',
                    'configurations' => [],
                    'domains' => [],
                    'modules' => [],
                ],
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn('like_api_key');

        $telemetry = \Mockery::mock('MerchantTelemetry');
        $telemetry->shouldReceive([
            'send' => [
                'result' => false,
            ],
        ]);
        $this->plugin->shouldReceive([
            'getMerchantTelemetry' => $telemetry,
        ]);

        $this->assertFalse($this->action->sendAction($source));
    }

    public function testWhenMerchantTelemetryIsSend()
    {
        $source = 'source';
        $this->action->shouldReceive([
            'renderTelemetries' => [
                'result' => true,
                'telemetries' => [
                    'version' => '4.x.x',
                    'name' => 'payplug',
                    'configurations' => [],
                    'domains' => [],
                    'modules' => [],
                ],
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn('like_api_key');

        $telemetry = \Mockery::mock('MerchantTelemetry');
        $telemetry->shouldReceive([
            'send' => [
                'result' => true,
            ],
        ]);
        $this->plugin->shouldReceive([
            'getMerchantTelemetry' => $telemetry,
        ]);

        $this->assertTrue($this->action->sendAction($source));
    }
}
