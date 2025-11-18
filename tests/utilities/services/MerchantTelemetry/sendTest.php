<?php

namespace PayPlug\tests\utilities\services\MerchantTelemetry;

use PayPlug\src\utilities\services\MerchantTelemetry;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group service
 * @group merchant_telemetry_service
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

    protected function tearDown()
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $data
     */
    public function testWhenGivenDatasHasInvalidStringFormat($data)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Invalid argument given, $datas must be a non empty string',
            ],
            $this->service->send($data)
        );
    }

    public function testWhenNotFoundExceptionIsThrown()
    {
        $this->plugin_telemetry
            ->shouldReceive('Send')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->send($this->data)
        );
    }

    public function testWhenTelemetryIsSend()
    {
        $this->plugin_telemetry->shouldReceive([
            'Send' => [
                'httpStatus' => 201,
            ],
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 201,
                'message' => '',
            ],
            $this->service->send($this->data)
        );
    }
}
