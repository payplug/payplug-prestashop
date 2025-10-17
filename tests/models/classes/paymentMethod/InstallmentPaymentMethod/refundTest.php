<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\mock\RefundMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class refundTest extends BaseInstallmentPaymentMethod
{
    public $resource_id;
    public $amount;
    public $metadata;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'inst_azerty1234';
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

    public function testWhenNoScheduleCantBeRefunded()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(),
                'schedule' => [
                    [
                        'amount' => 42,
                        'date' => '1970-01-01 00:00:00',
                        'resource' => PaymentMock::getStandard([
                            'failure' => [
                                'code' => '401',
                                'message' => 'Non authorized',
                            ],
                        ]),
                    ],
                ],
            ],
        ]);
        $this->payment_method->shouldReceive([
            'refund' => [
                'code' => 500,
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'No refund executed for this installment plan.',
            ],
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenScheduleCantBeUpdated()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(),
                'schedule' => [
                    [
                        'amount' => 42,
                        'date' => '1970-01-01 00:00:00',
                        'resource' => PaymentMock::getStandard([
                            'failure' => [
                                'code' => '401',
                                'message' => 'Non authorized',
                            ],
                        ]),
                    ],
                ],
            ],
            'updateInstallmentSchedules' => false,
        ]);
        $this->payment_method->shouldReceive([
            'refund' => [
                'result' => true,
                'code' => 200,
                'resource' => RefundMock::get(),
            ],
        ]);
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Can\'t update the schedule.',
            ],
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenScheduleIsRefunded()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(),
                'schedule' => [
                    [
                        'amount' => 42,
                        'date' => '1970-01-01 00:00:00',
                        'resource' => PaymentMock::getStandard([
                            'failure' => [
                                'code' => '401',
                                'message' => 'Non authorized',
                            ],
                        ]),
                    ],
                ],
            ],
            'updateInstallmentSchedules' => true,
        ]);
        $refund = [
            'result' => true,
            'code' => 200,
            'resource' => RefundMock::get(),
        ];
        $this->payment_method->shouldReceive([
            'refund' => $refund,
        ]);
        $this->assertSame(
            $refund,
            $this->class->refund($this->resource_id, $this->amount, $this->metadata)
        );
    }
}
