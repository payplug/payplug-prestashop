<?php

namespace PayPlug\tests\utilities\services\API;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class CreatePaymentTest extends BaseApi
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $attributes
     */
    public function testWhenGivenAttributesIsntValidArray($attributes)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $attributes given',
            ],
            $this->service->createPayment($attributes)
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
            $this->service->createPayment($this->resource_attribute)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->payment
            ->shouldReceive('create')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->createPayment($this->resource_attribute)
        );
    }

    public function testWhenPaymentIsCreated()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $resource = PaymentMock::getStandard();
        $this->payment->shouldReceive([
            'create' => $resource,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->createPayment($this->resource_attribute)
        );
    }
}
