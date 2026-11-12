<?php

namespace PayPlug\tests\actions\UnifiedOrderDetailAction;

/**
 * @group unit
 * @group action
 * @group unified_order_detail_action
 */
class buildPaymentTest extends BaseUnifiedOrderDetailAction
{
    public function testBuildsTheSameDetailsAsARetailPaymentFromTheRecordedSnapshot()
    {
        $this->factory->shouldReceive('create')->never();

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertEquals([
            'id' => self::PAYMENT_ID,
            'status' => 'Paid',
            'status_code' => 'paid',
            'status_class' => 'pp_success',
            'amount' => 29.0,
            'amount_display' => 'USD 29.00',
            'card_brand' => 'Card VISA',
            'card_mask' => '**** **** **** 0001',
            'card_date' => '12/2029',
            'card_country' => null,
            'mode' => 'Live',
            'paid' => true,
            'authorization' => false,
            'date' => '10/11/2026',
            'error' => '',
            'tds' => 'Yes',
            'refund' => [
                'refunded' => 0.0,
                'refunded_display' => 'USD 0.00',
                'available' => 29.0,
                'available_display' => 'USD 29.00',
                'is_refunded' => false,
            ],
            'currency' => 'USD',
        ], $payment);
        $this->assertSame(29.0, $payment['amount']);
        $this->assertSame(0.0, $payment['refund']['refunded']);
    }

    public function testShowsAPartialRefundWithTheRefundedAmount()
    {
        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 1000);

        $this->assertSame('partially_refunded', $payment['status_code']);
        $this->assertSame('Partially refunded', $payment['status']);
        $this->assertSame('pp_neutral', $payment['status_class']);
        $this->assertSame(10.0, $payment['refund']['refunded']);
        $this->assertSame('USD 10.00', $payment['refund']['refunded_display']);
        $this->assertSame(19.0, $payment['refund']['available']);
        $this->assertFalse($payment['refund']['is_refunded']);
    }

    /**
     * @dataProvider fullRefundProvider
     *
     * @param int $refunded_cents
     */
    public function testShowsAFullRefund($refunded_cents)
    {
        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), $refunded_cents);

        $this->assertSame('refunded', $payment['status_code']);
        $this->assertSame('Refunded', $payment['status']);
        $this->assertSame('pp_warning', $payment['status_class']);
        $this->assertSame(0.0, $payment['refund']['available']);
        $this->assertTrue($payment['refund']['is_refunded']);
    }

    public function fullRefundProvider()
    {
        return [
            'exactly the amount paid' => [2900],
            'more than the amount paid' => [2950],
        ];
    }

    public function testConvertsTheAmountsOfAZeroDecimalCurrencyUnscaled()
    {
        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(2900), $this->currency('JPY'), 1000);

        $this->assertSame(2900.0, $payment['amount']);
        $this->assertSame('JPY 2,900.00', $payment['amount_display']);
        $this->assertSame(1000.0, $payment['refund']['refunded']);
        $this->assertSame('JPY', $payment['currency']);
    }

    public function testShowsTheTestModeInSandbox()
    {
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('1');

        $this->assertSame('Test', $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0)['mode']);
    }

    public function testShowsAFrictionlessPaymentAsNotThreeDsAuthenticated()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(array_merge(self::SNAPSHOT, ['tds' => 'no']));

        $this->assertSame('No', $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0)['tds']);
    }

    public function testHidesTheMissingCardFields()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn([
            'brand' => null,
            'last4' => '0001',
            'exp_month' => '12',
            'exp_year' => null,
            'tds' => null,
        ]);

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertNull($payment['card_brand']);
        $this->assertSame('**** **** **** 0001', $payment['card_mask']);
        $this->assertNull($payment['card_date']);
        $this->assertFalse($payment['tds']);
    }

    public function testFetchesAndRecordsTheDetailsOfAnOrderWithoutSnapshot()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $this->mockGetOperation([
            'operationType' => 'PAYMENT',
            'execCode' => '0000',
            'amount' => 2900,
            'orderId' => '6',
            'authentication' => ['globalStatus' => 'OK'],
            'paymentMethod' => [
                'card' => ['code6x4' => '402205XXXXXX0001', 'type' => 'VISA'],
                'details' => ['validityDate' => '2029-12'],
            ],
        ]);
        $this->operation_repository->shouldReceive('savePaymentDetails')->once()->with(self::OPERATION_ID, self::SNAPSHOT)->andReturn(true);
        $this->token_cache->shouldReceive('set')->never();

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertSame('Card VISA', $payment['card_brand']);
        $this->assertSame('12/2029', $payment['card_date']);
        $this->assertSame('Yes', $payment['tds']);
    }

    /**
     * The snapshot column is missing on an install that ran an earlier build of the upgrade.
     */
    public function testStillShowsTheFetchedDetailsWhenTheyCannotBeRecorded()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $this->mockGetOperation(['execCode' => '0000', 'amount' => 2900, 'paymentMethod' => ['card' => ['type' => 'VISA']]]);
        $this->operation_repository->shouldReceive('savePaymentDetails')->once()->andReturn(false);
        $this->expectFetchFailureMarker();
        $this->logger->shouldReceive('error')->once()->with(\Mockery::pattern('/details of operation ' . self::OPERATION_ID . ' not recorded/'));

        $this->assertSame('Card VISA', $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0)['card_brand']);
    }

    public function testStillShowsTheFetchedDetailsWhenRecordingThemThrows()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $this->mockGetOperation(['execCode' => '0000', 'amount' => 2900, 'paymentMethod' => ['card' => ['type' => 'VISA']]]);
        $this->operation_repository->shouldReceive('savePaymentDetails')->once()->andThrow(new \Exception("Unknown column 'payment_details'"));
        $this->expectFetchFailureMarker();
        $this->logger->shouldReceive('error')->once()->with(\Mockery::pattern('/details of operation ' . self::OPERATION_ID . " not recorded.*Unknown column 'payment_details'/"));

        $this->assertSame('Card VISA', $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0)['card_brand']);
    }

    public function testSkipsTheFetchWhileARecentOneFailed()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $this->token_cache->shouldReceive('get')->with('uhf_details_fetch_failed:' . self::OPERATION_ID)->andReturn('1');
        $this->factory->shouldReceive('create')->never();
        $this->operation_repository->shouldReceive('savePaymentDetails')->never();

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertSame(self::PAYMENT_ID, $payment['id']);
        $this->assertSame('paid', $payment['status_code']);
        $this->assertNull($payment['card_brand']);
        $this->assertNull($payment['card_mask']);
        $this->assertFalse($payment['tds']);
    }

    public function testStillFetchesTheDetailsWhenTheFailureMarkerCannotBeRead()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $this->token_cache->shouldReceive('get')->andThrow(new \Exception('cache table missing'));
        $this->logger->shouldReceive('error')->once()->with(\Mockery::pattern('/fetch failure marker of operation ' . self::OPERATION_ID . ' .*cache table missing/'));
        $this->mockGetOperation(['execCode' => '0000', 'amount' => 2900, 'paymentMethod' => ['card' => ['type' => 'VISA']]]);
        $this->operation_repository->shouldReceive('savePaymentDetails')->once()->andReturn(true);

        $this->assertSame('Card VISA', $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0)['card_brand']);
    }

    public function testStillBuildsTheDetailsWhenTheFailureMarkerCannotBeWritten()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $this->factory->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')->once()->andThrow(new \Exception('HTTP status 503'));
        $this->token_cache->shouldReceive('set')->once()->andThrow(new \Exception('cache table missing'));
        $this->logger->shouldReceive('error')->once()->with(\Mockery::pattern('/details of operation ' . self::OPERATION_ID . ' not available/'));
        $this->logger->shouldReceive('error')->once()->with(\Mockery::pattern('/fetch failure marker of operation ' . self::OPERATION_ID . ' .*cache table missing/'));

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertSame('paid', $payment['status_code']);
        $this->assertNull($payment['card_brand']);
    }

    public function testShowsTheLocallyKnownDetailsWhenTheOperationCannotBeFetched()
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $payment_service = \Mockery::mock('UnifiedApiPaymentService');
        $this->factory->shouldReceive('create')->andReturn($payment_service);
        $payment_service->shouldReceive('getOperation')->once()->andThrow(new \Exception('Unified API operation request failed with HTTP status 503.'));
        $this->operation_repository->shouldReceive('savePaymentDetails')->never();
        $this->expectFetchFailureMarker();
        $this->logger->shouldReceive('error')->once()->with(\Mockery::pattern('/details of operation ' . self::OPERATION_ID . ' .*HTTP status 503/'));

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertSame(self::PAYMENT_ID, $payment['id']);
        $this->assertSame('paid', $payment['status_code']);
        $this->assertSame('USD 29.00', $payment['amount_display']);
        $this->assertSame('Live', $payment['mode']);
        $this->assertSame('10/11/2026', $payment['date']);
        $this->assertNull($payment['card_brand']);
        $this->assertNull($payment['card_mask']);
        $this->assertFalse($payment['tds']);
    }

    /**
     * @dataProvider unusableOperationProvider
     *
     * @param array<string, mixed>|string $body
     */
    public function testRecordsNoDetailsFromAnUnusableOperation($body)
    {
        $this->operation_repository->shouldReceive('getPaymentDetails')->andReturn(null);
        $this->mockGetOperation($body);
        $this->operation_repository->shouldReceive('savePaymentDetails')->never();
        $this->expectFetchFailureMarker();
        $this->logger->shouldReceive('error')->once()->with(\Mockery::pattern('/details of operation ' . self::OPERATION_ID . ' /'));

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertNull($payment['card_brand']);
        $this->assertSame('paid', $payment['status_code']);
    }

    public function unusableOperationProvider()
    {
        return [
            'not json' => ['<html>Bad gateway</html>'],
            'refused operation' => [['execCode' => '4001', 'paymentMethod' => ['card' => ['type' => 'VISA']]]],
            'no exec code' => [['paymentMethod' => ['card' => ['type' => 'VISA']]]],
        ];
    }

    public function testShowsNoTransactionIdWhenNoPaymentIdIsRecorded()
    {
        $this->operation_repository->shouldReceive('getPaymentIdByOperationId')->andReturn(null);

        $payment = $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0);

        $this->assertArrayNotHasKey('id', $payment);
        $this->assertSame('paid', $payment['status_code']);
    }

    public function testNeverUpdatesTheOrderPayment()
    {
        $this->order_action->shouldReceive('syncOrderPayment')->never();

        $this->assertSame(self::PAYMENT_ID, $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0)['id']);
    }

    public function testDatesAnUndatedOperationWithTheOrder()
    {
        $this->operation_repository->shouldReceive('getDateAddByOperationId')->andReturn(null);

        $this->assertSame('09/11/2026', $this->action->buildPayment($this->order(), $this->paidOperation(), $this->currency(), 0)['date']);
    }

    public function testShowsNoDateWhenNeitherTheOperationNorTheOrderIsDated()
    {
        $this->operation_repository->shouldReceive('getDateAddByOperationId')->andReturn(null);

        $this->assertNull($this->action->buildPayment($this->order(null), $this->paidOperation(), $this->currency(), 0)['date']);
    }

    private function expectFetchFailureMarker()
    {
        $this->token_cache->shouldReceive('set')->once()->with('uhf_details_fetch_failed:' . self::OPERATION_ID, \Mockery::type('string'), 300);
    }
}
