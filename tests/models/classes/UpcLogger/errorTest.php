<?php

namespace PayPlug\tests\models\classes\UpcLogger;

/**
 * @group unit
 * @group unified
 */
class errorTest extends BaseUpcLogger
{
    public function testErrorLogsWithErrorLevel()
    {
        $this->logger_repository->shouldReceive('addLog')->once()->with('an error message', 'error');

        $this->logger->error('an error message');
        $this->addToAssertionCount(1);
    }

    public function testContextIsAppendedAsJson()
    {
        $this->logger_repository->shouldReceive('addLog')->once()->with('a message {"cart_id":42}', 'error');

        $this->logger->error('a message', ['cart_id' => 42]);
        $this->addToAssertionCount(1);
    }
}
