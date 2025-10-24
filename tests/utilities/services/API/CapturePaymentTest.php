<?php

namespace PayPlug\tests\utilities\services\API;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class CapturePaymentTest extends BaseApi
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
            $this->service->capturePayment($resource_id)
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
            $this->service->capturePayment($this->resource_id)
        );
    }

    public function testWhenNotAllowedExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('capture')
            ->andThrow(new \Payplug\Exception\NotAllowedException('An error occured during the process', '', 405));

        $this->assertSame(
            [
                'result' => false,
                'code' => 405,
                'message' => 'An error occured during the process',
            ],
            $this->service->capturePayment($this->resource_id)
        );
    }

    public function testWhenNotForbiddenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('capture')
            ->andThrow(new \Payplug\Exception\ForbiddenException('An error occured during the process', '', 403));

        $this->assertSame(
            [
                'result' => false,
                'code' => 403,
                'message' => 'An error occured during the process',
            ],
            $this->service->capturePayment($this->resource_id)
        );
    }

    public function testWhenConfigurationNotSetExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('capture')
            ->andThrow(new \Payplug\Exception\ConfigurationNotSetException('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->capturePayment($this->resource_id)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('capture')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->capturePayment($this->resource_id)
        );
    }

    public function testWhenPaymentIsCaptured()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $resource = PaymentMock::getStandard();
        $this->payment->shouldReceive([
            'capture' => $resource,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->capturePayment($this->resource_id)
        );
    }
}
