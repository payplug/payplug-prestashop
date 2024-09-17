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
class refundActionTest extends BaseRefundAction
{
    private $resource_id;
    private $amount;
    private $metadata;
    private $pay_mode;
    private $resource;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'pay_azerty12345';
        $this->amount = 100;
        $this->metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];
        $this->pay_mode = 'LIVE';
        $this->resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
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
            $this->action->refundAction($resource_id, $this->amount, $this->metadata)
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
            $this->action->refundAction($this->resource_id, $amount, $this->metadata)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $metadata
     */
    public function testWhenGivenMetaDataIdIsntValidArray($metadata)
    {
        $this->assertSame(
            [],
            $this->action->refundAction($this->resource_id, $this->amount, $metadata)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $pay_mode
     */
    public function testWhenGivenPayModeIdIsntValidString($pay_mode)
    {
        $this->assertSame(
            [],
            $this->action->refundAction($this->resource_id, $this->amount, $this->metadata, $pay_mode)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_installment
     */
    public function testWhenGivenIsInstallmentIdIsntValidBoolean($is_installment)
    {
        $this->assertSame(
            [],
            $this->action->refundAction($this->resource_id, $this->amount, $this->metadata, $this->pay_mode, $is_installment)
        );
    }

    public function testWhenInstallmentResourceCantBeRefunded()
    {
        $this->action->shouldReceive([
            'processInstallmentRefund' => [],
        ]);
        $this->assertSame(
            [],
            $this->action->refundAction($this->resource_id, $this->amount, $this->metadata, $this->pay_mode, true)
        );
    }

    public function testWhenInstallmentResourceIsRefunded()
    {
        $expected = [
            'id' => $this->resource_id,
            'data' => [
                'amount' => $this->amount,
                'metadata' => $this->metadata,
            ],
            'response' => $this->resource,
        ];
        $this->action->shouldReceive([
            'processInstallmentRefund' => $expected,
        ]);
        $this->assertSame(
            $expected,
            $this->action->refundAction($this->resource_id, $this->amount, $this->metadata, $this->pay_mode, true)
        );
    }

    public function testWhenPaymentResourceCantBeRefunded()
    {
        $this->action->shouldReceive([
            'processPaymentRefund' => [],
        ]);
        $this->assertSame(
            [],
            $this->action->refundAction($this->resource_id, $this->amount, $this->metadata, $this->pay_mode, false)
        );
    }

    public function testWhenPaymentResourceIsRefunded()
    {
        $expected = [
            'id' => $this->resource_id,
            'data' => [
                'amount' => $this->amount,
                'metadata' => $this->metadata,
            ],
            'response' => $this->resource,
        ];
        $this->action->shouldReceive([
            'processPaymentRefund' => $expected,
        ]);
        $this->assertSame(
            $expected,
            $this->action->refundAction($this->resource_id, $this->amount, $this->metadata, $this->pay_mode)
        );
    }
}
