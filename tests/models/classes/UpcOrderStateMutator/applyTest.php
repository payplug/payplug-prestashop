<?php

namespace PayPlug\tests\models\classes\UpcOrderStateMutator;

use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group class
 * @group upc_order_state_mutator
 */
final class applyTest extends BaseUpcOrderStateMutator
{
    public function refundedStateProvider()
    {
        return [
            'refund state' => [false, 50],
            'partial refund state' => [false, 40],
            'refund state in sandbox' => [true, 50],
            'partial refund state in sandbox' => [true, 40],
        ];
    }

    /**
     * @dataProvider refundedStateProvider
     *
     * @param bool $sandbox
     * @param int $current_state
     */
    public function testALatePaidOutcomeNeverMovesARefundedOrderBack($sandbox, $current_state)
    {
        $this->mockContext($sandbox, $current_state);
        $this->logger_repository->shouldReceive('addLog')
            ->once()
            ->with(\Mockery::pattern('/already-refunded order id: 12/'), 'info');
        $this->order_class->shouldReceive('updateOrderState')->never();

        $this->mutator->apply('12', PaymentOutcome::PAID);
        $this->addToAssertionCount(1);
    }

    public function testAPendingOrderIsStillMovedToPaid()
    {
        $this->mockContext(false, 30);
        $this->order_class->shouldReceive('updateOrderState')->once()->with($this->order, 20)->andReturn(true);

        $this->mutator->apply('12', PaymentOutcome::PAID);
        $this->addToAssertionCount(1);
    }

    public function testALateFailedOutcomeNeverMovesAPaidOrderBackAndIsNotAFailure()
    {
        $this->mockContext(false, 20);
        $this->logger_repository->shouldReceive('addLog')
            ->once()
            ->with(\Mockery::pattern('/Skipped regressive state transition/'), 'error');
        $this->order_class->shouldReceive('updateOrderState')->never();

        $this->mutator->apply('12', PaymentOutcome::FAILED);
        $this->addToAssertionCount(1);
    }

    public function testThrowsWhenTheOrderStateCannotBeUpdated()
    {
        $this->mockContext(false, 30);
        $this->order_class->shouldReceive('updateOrderState')->once()->with($this->order, 20)->andReturn(false);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/order id: 12/');

        $this->mutator->apply('12', PaymentOutcome::PAID);
    }

    public function testThrowsWhenTheOrderIsNotFound()
    {
        $this->order_adapter->shouldReceive('get')->with(404)->andReturn(null);
        $this->order_class->shouldReceive('updateOrderState')->never();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Order not found for id: 404/');

        $this->mutator->apply('404', PaymentOutcome::PAID);
    }

    public function testThrowsWhenNoOrderStateIsConfiguredForTheOutcome()
    {
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn(false);
        $this->order_class->shouldReceive('getOrderStates')->andReturn(['pending' => 30]);
        $this->order_class->shouldReceive('updateOrderState')->never();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/No order state configured for bucket: paid/');

        $this->mutator->apply('12', PaymentOutcome::PAID);
    }

    public function testThrowsOnAnUnknownOutcome()
    {
        $this->order_class->shouldReceive('updateOrderState')->never();

        $this->expectException(\RuntimeException::class);

        $this->mutator->apply('12', 'NOT_AN_OUTCOME');
    }
}
