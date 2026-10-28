<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\mock\RefundMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group satispay_payment_method_class
 */
class refundTest extends BaseSatispayPaymentMethod
{
    use MockeryPHPUnitIntegration;

    protected $metadata;

    public function setUp(): void
    {
        parent::setUp();

        $this->metadata = [
            'ID Client' => 1,
            'reason' => 'Refunded with Prestashop',
        ];
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard([
                    'is_paid' => true,
                    'is_live' => true,
                    'amount' => 4242,
                    'amount_refunded' => 0,
                ]),
            ],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(false);
    }

    public function refundAmountProvider()
    {
        yield 'total' => [4242];

        yield 'partial' => [1000];
    }

    /**
     * @dataProvider refundAmountProvider
     *
     * @param int $amount
     */
    public function testRefundIsSentForTheRequestedAmount($amount)
    {
        $refund = [
            'result' => true,
            'resource' => RefundMock::get(),
        ];
        $this->api_service->shouldReceive('refundPayment')
            ->once()
            ->with('pay_azerty1234', ['amount' => $amount, 'metadata' => $this->metadata])
            ->andReturn($refund);

        $this->assertSame($refund, $this->class->refund('pay_azerty1234', $amount, $this->metadata));
    }
}
