<?php

namespace PayPlug\tests\models\classes\UpcOrderStateMutator;

use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group unified
 */
class applyTest extends BaseUpcOrderStateMutator
{
    private $order_states = [
        'auth' => 10,
        'cancelled' => 11,
        'error' => 12,
        'expired' => 13,
        'oney_pg' => 14,
        'outofstock_paid' => 15,
        'outofstock_unpaid' => 16,
        'paid' => 17,
        'pending' => 18,
        'refund' => 19,
    ];

    public function testLogsAndReturnsWhenOutcomeIsInvalid()
    {
        $this->logger->shouldReceive('addLog')->once()->with(\Mockery::type('string'), 'error');
        $this->order_adapter->shouldReceive('get')->never();

        $this->mutator->apply('42', 'not_a_real_outcome');
        $this->addToAssertionCount(1);
    }

    public function testLogsAndReturnsWhenOrderIsNotFound()
    {
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn(null);
        $this->logger->shouldReceive('addLog')->once()->with(\Mockery::type('string'), 'error');

        $this->mutator->apply('42', PaymentOutcome::PAID);
        $this->addToAssertionCount(1);
    }

    public function testLogsAndReturnsWhenOrderIdIsZero()
    {
        $this->order->id = 0;
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn($this->order);
        $this->logger->shouldReceive('addLog')->once()->with(\Mockery::type('string'), 'error');

        $this->mutator->apply('42', PaymentOutcome::PAID);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider bucketDataProvider
     *
     * @param string $outcome
     * @param string $bucket
     */
    public function testMapsOutcomeToTheExpectedBucketAndAppliesIt($outcome, $bucket)
    {
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn($this->order);
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $this->order_class->shouldReceive('getOrderStates')->with(true)->andReturn($this->order_states);
        $this->order->shouldReceive('getCurrentState')->andReturn($this->order_states['pending']);
        $this->order_class->shouldReceive('updateOrderState')
            ->once()
            ->with($this->order, $this->order_states[$bucket]);

        $this->mutator->apply('42', $outcome);
        $this->addToAssertionCount(1);
    }

    public function bucketDataProvider()
    {
        return [
            'paid outcome maps to the paid bucket' => [PaymentOutcome::PAID, 'paid'],
            'authorized outcome maps to the auth bucket' => [PaymentOutcome::AUTHORIZED, 'auth'],
            'capture_required outcome maps to the auth bucket' => [PaymentOutcome::CAPTURE_REQUIRED, 'auth'],
            'three_ds_pending outcome maps to the pending bucket' => [PaymentOutcome::THREE_DS_PENDING, 'pending'],
            'refunded outcome maps to the refund bucket' => [PaymentOutcome::REFUNDED, 'refund'],
            'failed outcome maps to the error bucket' => [PaymentOutcome::FAILED, 'error'],
        ];
    }

    public function testLogsAndReturnsWhenNoOrderStateIsConfiguredForTheBucket()
    {
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn($this->order);
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $this->order_class->shouldReceive('getOrderStates')->with(true)->andReturn([]);
        $this->logger->shouldReceive('addLog')->once()->with(\Mockery::type('string'), 'error');
        $this->order_class->shouldReceive('updateOrderState')->never();

        $this->mutator->apply('42', PaymentOutcome::PAID);
        $this->addToAssertionCount(1);
    }

    public function testSkipsARegressiveTransitionFromPaidToErrorAndLogsInstead()
    {
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn($this->order);
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $this->order_class->shouldReceive('getOrderStates')->with(true)->andReturn($this->order_states);
        $this->order->shouldReceive('getCurrentState')->andReturn($this->order_states['paid']);
        $this->order_class->shouldReceive('updateOrderState')->never();
        $this->logger->shouldReceive('addLog')->once()->with(\Mockery::type('string'), 'error');

        $this->mutator->apply('42', PaymentOutcome::FAILED);
        $this->addToAssertionCount(1);
    }

    public function testSkipsARegressiveTransitionFromPaidToRefundAndLogsInstead()
    {
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn($this->order);
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $this->order_class->shouldReceive('getOrderStates')->with(true)->andReturn($this->order_states);
        $this->order->shouldReceive('getCurrentState')->andReturn($this->order_states['paid']);
        $this->order_class->shouldReceive('updateOrderState')->never();
        $this->logger->shouldReceive('addLog')->once()->with(\Mockery::type('string'), 'error');

        $this->mutator->apply('42', PaymentOutcome::REFUNDED);
        $this->addToAssertionCount(1);
    }

    public function testAppliesAnErrorOutcomeNormallyWhenTheOrderIsNotAlreadyPaid()
    {
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn($this->order);
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $this->order_class->shouldReceive('getOrderStates')->with(true)->andReturn($this->order_states);
        $this->order->shouldReceive('getCurrentState')->andReturn($this->order_states['pending']);
        $this->order_class->shouldReceive('updateOrderState')
            ->once()
            ->with($this->order, $this->order_states['error']);

        $this->mutator->apply('42', PaymentOutcome::FAILED);
        $this->addToAssertionCount(1);
    }

    public function testAppliesAPaidOutcomeNormallyWhenTheOrderIsAlreadyPaid()
    {
        // Same-state re-application (paid -> paid) is not a regressive bucket, so the guard
        // must not block it - Order::updateOrderState() already handles that no-op itself
        // (with its own log), which this test does not need to duplicate.
        $this->order_adapter->shouldReceive('get')->with(42)->andReturn($this->order);
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $this->order_class->shouldReceive('getOrderStates')->with(true)->andReturn($this->order_states);
        $this->order->shouldReceive('getCurrentState')->andReturn($this->order_states['paid']);
        $this->order_class->shouldReceive('updateOrderState')
            ->once()
            ->with($this->order, $this->order_states['paid']);

        $this->mutator->apply('42', PaymentOutcome::PAID);
        $this->addToAssertionCount(1);
    }
}
