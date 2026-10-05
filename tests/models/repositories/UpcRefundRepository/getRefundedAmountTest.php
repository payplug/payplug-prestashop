<?php

namespace PayPlug\tests\models\repositories\UpcRefundRepository;

/**
 * @group unit
 * @group repository
 * @group upc_refund_repository
 */
class getRefundedAmountTest extends BaseUpcRefundRepository
{
    public function testSumsPendingAndConfirmedRowsButNotFailedOnes()
    {
        $this->repository->shouldReceive('getAllBy')->with('order_id', '42')->andReturn([
            ['amount' => '1000', 'status' => 'pending'],
            ['amount' => '500', 'status' => 'confirmed'],
            ['amount' => '300', 'status' => 'failed'],
        ]);

        $this->assertSame(1500, $this->repository->getRefundedAmount('42'));
    }

    public function testReturnsZeroWhenTheOrderHasNoRefund()
    {
        $this->repository->shouldReceive('getAllBy')->with('order_id', '42')->andReturn([]);

        $this->assertSame(0, $this->repository->getRefundedAmount('42'));
    }
}
