<?php

namespace PayPlug\tests\unified\contract\UnifiedLogger;

/**
 * @group unit
 * @group unified
 */
class infoTest extends BaseUnifiedLogger
{
    public function testLogsTheMessageAtLevelInfo()
    {
        $this->logger_repository->shouldReceive('addLog')->once()->with('Payment created', 'info');

        $this->logger->info('Payment created');
        $this->addToAssertionCount(1);
    }

    public function testAppendsTheContextAsJson()
    {
        $this->logger_repository->shouldReceive('addLog')
            ->once()
            ->with('Payment created {"operationId":"op_1","amount":1789}', 'info');

        $this->logger->info('Payment created', ['operationId' => 'op_1', 'amount' => 1789]);
        $this->addToAssertionCount(1);
    }

    public function testDoesNotLogAnEmptyMessage()
    {
        $this->logger_repository->shouldReceive('addLog')->never();

        $this->logger->info('', ['operationId' => 'op_1']);
        $this->addToAssertionCount(1);
    }
}
