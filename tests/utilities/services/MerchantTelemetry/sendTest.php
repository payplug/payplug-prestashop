<?php

namespace PayPlug\tests\utilities\services\MerchantTelemetry;

use PayPlug\src\utilities\services\MerchantTelemetry;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group service
 * @group merchant_telemetry_service
 *
 * @runTestsInSeparateProcesses
 */
class sendTest extends TestCase
{
    use FormatDataProvider;

    protected $data;
    protected $service;
    protected $plugin_telemetry;

    public function setUp()
    {
        $this->data = 'some_json_data';
        $this->service = new MerchantTelemetry();
        $this->plugin_telemetry = \Mockery::mock('alias:Payplug\PluginTelemetry');
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $datas
     */
    public function testWhenGivenDatasHasInvalidStringFormat($datas)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Invalid argument given, $datas must be a non empty string',
            ],
            $this->service->send($datas)
        );
    }

    public function testWhenNotFoundExceptionIsThrown()
    {
        $this->plugin_telemetry
            ->shouldReceive('Send')
            ->andThrow(new \Exception('An error occured during the process', 404));

        $this->assertSame(
            [
                'result' => false,
                'code' => 404,
                'message' => 'An error occured during the process',
            ],
            $this->service->send($this->data)
        );
    }

    public function testWhenTelemetryIsSend()
    {
        $success_code = 201;
        $this->plugin_telemetry->shouldReceive([
            'Send' => [
                'httpStatus' => $success_code,
            ],
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => $success_code,
                'message' => '',
            ],
            $this->service->send($this->data)
        );
    }
}
