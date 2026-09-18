<?php

namespace PayPlug\tests\models\classes\UpcLogger;

/**
 * @group unit
 * @group unified
 */
class infoTest extends BaseUpcLogger
{
    public function testInfoLogsWithInfoLevelAndNoContext()
    {
        $this->logger_repository->shouldReceive('addLog')->once()->with('a message', 'info');

        $this->logger->info('a message');
        $this->addToAssertionCount(1);
    }

    public function testAnEmptyMessageIsANoOp()
    {
        $this->logger_repository->shouldNotReceive('addLog');

        $this->logger->info('');
        $this->addToAssertionCount(1);
    }
}
