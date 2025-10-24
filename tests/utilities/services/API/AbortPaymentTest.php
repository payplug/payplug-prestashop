<?php

namespace PayPlug\tests\utilities\services\API;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class AbortPaymentTest extends BaseApi
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsntValidString($resource_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ],
            $this->service->abortPayment($resource_id)
        );
    }

    public function testWhenAPiCantBeInitialize()
    {
        $this->service->shouldReceive([
            'initialize' => false,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'Cannot connect to the API',
            ],
            $this->service->abortPayment($this->resource_id)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('abort')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->abortPayment($this->resource_id)
        );
    }

    public function testWhenPaymentIsAborted()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $resource = PaymentMock::getStandard();
        $this->payment->shouldReceive([
            'abort' => $resource,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->abortPayment($this->resource_id)
        );
    }
}
