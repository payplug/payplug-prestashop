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

    protected $service;

    public function setUp()
    {
        $this->service = new MerchantTelemetry();
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $api_key
     */
    public function testWhenGivenApiKeyHasInvalidStringFormat($api_key)
    {
        $datas = 'string value';
        $this->assertSame(
            [
                'code' => null,
                'result' => false,
                'message' => 'Invalid argument given, $api_key must be a non empty string',
            ],
            $this->service->send($api_key, $datas)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $datas
     */
    public function testWhenGivenDatasHasInvalidStringFormat($datas)
    {
        $api_key = 'string value';
        $this->assertSame(
            [
                'code' => null,
                'result' => false,
                'message' => 'Invalid argument given, $datas must be a non empty string',
            ],
            $this->service->send($api_key, $datas)
        );
    }

    public function testWhenSendReturnAnError()
    {
    }

    public function testWhenSendReturn()
    {
    }
}
