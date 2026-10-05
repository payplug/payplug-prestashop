<?php

namespace PayPlug\tests\models\classes\UpcLogger;

/**
 * @group unit
 * @group unified
 */
class errorTest extends BaseUpcLogger
{
    public function testLogsTheMessageAtLevelError()
    {
        $this->logger_repository->shouldReceive('addLog')->once()->with('Payment created', 'error');

        $this->logger->error('Payment created');
        $this->addToAssertionCount(1);
    }

    public function testAppendsTheContextAsJson()
    {
        $this->logger_repository->shouldReceive('addLog')
            ->once()
            ->with('Payment created {"operationId":"op_1","amount":1789}', 'error');

        $this->logger->error('Payment created', ['operationId' => 'op_1', 'amount' => 1789]);
        $this->addToAssertionCount(1);
    }

    public function testDoesNotLogAnEmptyMessage()
    {
        $this->logger_repository->shouldReceive('addLog')->never();

        $this->logger->error('', ['operationId' => 'op_1']);
        $this->addToAssertionCount(1);
    }
}
