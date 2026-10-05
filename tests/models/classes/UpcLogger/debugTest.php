<?php

namespace PayPlug\tests\models\classes\UpcLogger;

/**
 * @group unit
 * @group unified
 */
class debugTest extends BaseUpcLogger
{
    public function testLogsTheMessageAtLevelInfo()
    {
        $this->logger_repository->shouldReceive('addLog')->once()->with('Payment created', 'info');

        $this->logger->debug('Payment created');
        $this->addToAssertionCount(1);
    }

    public function testAppendsTheContextAsJson()
    {
        $this->logger_repository->shouldReceive('addLog')
            ->once()
            ->with('Payment created {"operationId":"op_1","amount":1789}', 'info');

        $this->logger->debug('Payment created', ['operationId' => 'op_1', 'amount' => 1789]);
        $this->addToAssertionCount(1);
    }

    public function testDoesNotLogAnEmptyMessage()
    {
        $this->logger_repository->shouldReceive('addLog')->never();

        $this->logger->debug('', ['operationId' => 'op_1']);
        $this->addToAssertionCount(1);
    }
}
