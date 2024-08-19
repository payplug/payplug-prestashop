<?php

namespace PayPlug\tests\actions\QueueAction;

/**
 * @group unit
 * @group action
 * @group queue_action
 *
 * @runTestsInSeparateProcesses
 */
class updateActionTest extends BaseQueueAction
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testWhenGivenIdCartIsInvalidIntegerFormat($id_cart)
    {
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->updateAction($id_cart)
        );
    }

    public function testWhenNoQueueFoundInDatabase()
    {
        $this->repository
            ->shouldReceive([
                'getFirstNotTreatedEntry' => [],
            ]);

        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->updateAction($this->id_cart)
        );
    }

    public function testWhenQueueCantBeUpdated()
    {
        $this->repository
            ->shouldReceive([
                'getFirstNotTreatedEntry' => true,
                'updateEntity' => false,
            ]);

        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->updateAction($this->id_cart)
        );
    }

    public function testWhenQueueIsUpdatedAndNeedToBeTreated()
    {
        $this->repository
            ->shouldReceive([
                'getFirstNotTreatedEntry' => true,
                'updateEntity' => true,
            ]);

        $this->assertSame(
            [
                'exists' => true,
                'result' => true,
            ],
            $this->action->updateAction($this->id_cart)
        );
    }

    public function testWhenQueueIsUpdatedAndTreated()
    {
        $this->repository
            ->shouldReceive([
                'updateEntity' => true,
            ]);

        $this->repository
            ->shouldReceive('getFirstNotTreatedEntry')
            ->once()
            ->andReturn(true);

        $this->repository
            ->shouldReceive('getFirstNotTreatedEntry')
            ->once()
            ->andReturn(false);

        $this->assertSame(
            [
                'exists' => false,
                'result' => true,
            ],
            $this->action->updateAction($this->id_cart)
        );
    }
}
