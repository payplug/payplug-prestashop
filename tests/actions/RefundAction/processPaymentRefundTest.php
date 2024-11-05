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
class processPaymentRefundTest extends BaseRefundAction
{
    private $resource_id;
    private $amount;
    private $metadata;
    private $resource;
    private $stored_resource;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'pay_azerty12345';
        $this->amount = 100;
        $this->metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];
        $this->resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->stored_resource = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
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
            $this->action->processPaymentRefund($resource_id, $this->amount, $this->metadata)
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
            $this->action->processPaymentRefund($this->resource_id, $amount, $this->metadata)
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
            $this->action->processPaymentRefund($this->resource_id, $this->amount, $metadata)
        );
    }

    public function testWhenStoredResourceAndScheduleCantBeGetted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
            'getFromSchedule' => [],
        ]);
        $this->assertSame(
            [],
            $this->action->processPaymentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenPaymentResourceCantBeRetrieved()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [],
            $this->action->processPaymentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenRefundableAmountIsNotPaid()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => $this->resource,
        ]);
        $this->assertSame(
            [],
            $this->action->processPaymentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenRefundableAmountIsAlreadyRefunded()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $retrieve = $this->resource;
        $retrieve['resource'] = PaymentMock::getStandard([
            'is_paid' => true,
            'is_refunded' => true,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => $retrieve,
        ]);
        $this->assertSame(
            [],
            $this->action->processPaymentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }

    public function testWhenResourceIsRefunded()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $retrieve = $this->resource;
        $retrieve['resource'] = PaymentMock::getStandard([
            'is_paid' => true,
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => $retrieve,
        ]);

        $this->api_service->shouldReceive([
            'refundPayment' => $this->resource,
        ]);
        $this->assertSame(
            [
                'id' => $this->resource_id,
                'data' => [
                    'amount' => $this->amount,
                    'metadata' => $this->metadata,
                ],
                'response' => $this->resource,
            ],
            $this->action->processPaymentRefund($this->resource_id, $this->amount, $this->metadata)
        );
    }
}
