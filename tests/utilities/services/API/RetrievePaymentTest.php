<?php

namespace PayPlug\tests\utilities\services\API;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class RetrievePaymentTest extends BaseApi
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
            $this->service->retrievePayment($resource_id)
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
            $this->service->retrievePayment($this->resource_id)
        );
    }

    public function testWhenConfigurationNotSetExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('retrieve')
            ->andThrow(new \Payplug\Exception\ConfigurationNotSetException('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->retrievePayment($this->resource_id)
        );
    }

    public function testWhenNotFoundExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('retrieve')
            ->andThrow(new \Payplug\Exception\NotFoundException('An error occured during the process', '', 404));

        $this->assertSame(
            [
                'result' => false,
                'code' => 404,
                'message' => 'An error occured during the process',
            ],
            $this->service->retrievePayment($this->resource_id)
        );
    }

    public function testWhenUndefinedAttributeExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('retrieve')
            ->andThrow(new \Payplug\Exception\UndefinedAttributeException('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->retrievePayment($this->resource_id)
        );
    }

    public function testWhenInstallmentIsRetrieved()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $resource = PaymentMock::getStandard();
        $this->payment->shouldReceive([
            'retrieve' => $resource,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->retrievePayment($this->resource_id)
        );
    }
}
