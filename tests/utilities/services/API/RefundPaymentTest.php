<?php

namespace PayPlug\tests\utilities\services\API;

use PayPlug\tests\mock\RefundMock;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class RefundPaymentTest extends BaseApi
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
            $this->service->refundPayment($resource_id, $this->resource_attribute)
        );
    }

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
            $this->service->refundPayment($this->resource_id, $attributes)
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
            $this->service->refundPayment($this->resource_id, $this->resource_attribute)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);
        $this->refund
            ->shouldReceive('create')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->refundPayment($this->resource_id, $this->resource_attribute)
        );
    }

    public function testWhenPaymentIsPatched()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);
        $resource = RefundMock::get();
        $this->refund->shouldReceive([
            'create' => $resource,
        ]);
        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->refundPayment($this->resource_id, $this->resource_attribute)
        );
    }
}
