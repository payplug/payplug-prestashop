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
        $this->order_class->shouldReceive('updateOrderState')->once()->with($this->order, 20);

        $this->mutator->apply('12', PaymentOutcome::PAID);
        $this->addToAssertionCount(1);
    }
}
