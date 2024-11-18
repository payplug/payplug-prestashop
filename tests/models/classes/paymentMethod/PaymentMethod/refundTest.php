<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\mock\RefundMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_classe
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class refundTest extends BasePaymentMethod
{
    private $resource_id;
    private $amount;
    private $metadata;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'pay_azerty1234';
        $this->amount = 4242;
        $this->metadata = [
            'ID Client' => 1,
            'reason' => 'Refunded with Prestashop',
        ];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsntValidStringFormat($resource_id)
    {
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ],
            $this->class->refund($resource_id, $this->amount, $this->metadata)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsntValidIntegerFormat($amount)
    {
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $amount must be a non null integer.',
            ],
            $this->class->refund($this->resource_id, $amount, $this->metadata)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $metadata
     */
    public function testWhenGivenMetadataIsntValidStringFormat($metadata)
    {
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $metadata must be a non empty array.',
            ],
            $this->class->refund($this->resource_id, $this->amount, $metadata)
        );
    }

    public function testWhenResourceCantBeRetrieve()
    {
        $retrieve_error = [
            'code' => 500,
            'result' => false,
        ];
        $this->class->shouldReceive([
            'retrieve' => $retrieve_error,
        ]);
        $this->assertSame(
            $retrieve_error,
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenResourceIsNotPaid()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Payment resource is not paid.',
            ],
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenResourceIsAlreadyRefunded()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true, 'is_refunded' => true]),
            ],
        ]);
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Payment resource is fully refund.',
            ],
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenResourceCantBeRefunded()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true, 'is_live' => true]),
            ],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(false);
        $error = [
            'result' => false,
            'message' => 'An error occured',
        ];
        $this->api_service->shouldReceive([
            'refundPayment' => $error,
        ]);
        $this->assertSame(
            $error,
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenResourceIsRefunded()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true, 'is_live' => true]),
            ],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(false);
        $error = [
            'result' => true,
            'resource' => RefundMock::get(),
        ];
        $this->api_service->shouldReceive([
            'refundPayment' => $error,
        ]);
        $this->assertSame(
            $error,
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }
}
