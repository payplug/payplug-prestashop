<?php

namespace PayPlug\tests\models\classes\UpcLogger;

/**
 * @group unit
 * @group unified
 */
class debugTest extends BaseUpcLogger
{
    public function testDebugLogsWithInfoLevel()
    {
        $this->logger_repository->shouldReceive('addLog')->once()->with('a debug message', 'info');

        $this->logger->debug('a debug message');
        $this->addToAssertionCount(1);
    }
}
