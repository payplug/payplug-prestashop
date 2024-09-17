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
class PatchPaymentTest extends BaseApi
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
            $this->service->patchPayment($resource_id, $this->resource_attribute)
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
            $this->service->patchPayment($this->resource_id, $attributes)
        );
    }

    public function testWhenResourceCantBeRetrieve()
    {
        $retrieve = [
            'result' => false,
            'message' => 'An error occured during the resource retrieve',
        ];
        $this->service->shouldReceive([
            'retrievePayment' => $retrieve,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Can\'t patch the payment: ' . $retrieve['message'],
            ],
            $this->service->patchPayment($this->resource_id, $this->resource_attribute)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $retrieve = [
            'result' => true,
            'code' => 200,
            'resource' => $this->payment,
        ];
        $this->service->shouldReceive([
            'retrievePayment' => $retrieve,
        ]);
        $this->payment
            ->shouldReceive('update')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->patchPayment($this->resource_id, $this->resource_attribute)
        );
    }

    public function testWhenPaymentIsPatched()
    {
        $retrieve = [
            'result' => true,
            'code' => 200,
            'resource' => $this->payment,
        ];
        $this->service->shouldReceive([
            'retrievePayment' => $retrieve,
        ]);

        $resource = PaymentMock::getStandard();
        $this->payment->shouldReceive([
            'update' => $resource,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->patchPayment($this->resource_id, $this->resource_attribute)
        );
    }
}
