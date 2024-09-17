<?php

namespace PayPlug\tests\actions\RefundAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group refund_action
 *
 * @runTestsInSeparateProcesses
 */
class processInstallmentRefundTest extends BaseRefundAction
{
    private $resource_id;
    private $amount;
    private $metadata;
    private $resource;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'inst_5jjL5sWDZ5pkSty6eNjPtU';
        $this->amount = 100;
        $this->metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];
        $this->resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 42,
                    'date' => '1970-01-01 00:00:00',
                    'resource' => PaymentMock::getStandard(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsntValidString($resource_id)
    {
        $this->assertSame(
            [],
            $this->action->processInstallmentRefund($resource_id, $this->amount, $this->metadata)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIdIsntValidInteger($amount)
    {
        $this->assertSame(
            [],
            $this->action->processInstallmentRefund($this->resource_id, $amount, $this->metadata)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $metadata
     */
    public function testWhenGivenMetadataIdIsntValidArray($metadata)
    {
        $this->assertSame(
            [],
            $this->action->processInstallmentRefund($this->resource_id, $this->amount, $metadata)
        );
    }

    public function testWhenResourceCantBeRetrieved()
    {
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [],
            $this->action->processInstallmentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenScheduleResourceCantBeRefunded()
    {
        $this->resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 42,
                    'date' => '1970-01-01 00:00:00',
                    'resource' => PaymentMock::getStandard(),
                ],
            ],
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $this->resource,
        ]);
        $this->action->shouldReceive([
            'processPaymentRefund' => false,
        ]);
        $this->assertSame(
            [],
            $this->action->processInstallmentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenScheduleResourceIsRefunded()
    {
        $this->resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 42,
                    'date' => '1970-01-01 00:00:00',
                    'resource' => PaymentMock::getStandard(),
                ],
            ],
        ];
        $this->payment_method->shouldReceive([
            'retrieve' => $this->resource,
            'updateInstallmentSchedules' => true,
        ]);
        $expected = [
            'id' => $this->resource_id,
            'data' => [
                'amount' => $this->amount,
                'metadata' => $this->metadata,
            ],
            'response' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ];
        $this->action->shouldReceive([
            'processPaymentRefund' => $expected,
        ]);
        $this->assertSame(
            $expected['response']['resource'],
            $this->action->processInstallmentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }
}
