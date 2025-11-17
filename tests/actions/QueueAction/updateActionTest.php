<?php

namespace PayPlug\tests\actions\QueueAction;

/**
 * @group unit
 * @group action
 * @group queue_action
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
        $this->repository->shouldReceive([
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
        $this->repository->shouldReceive([
            'getFirstNotTreatedEntry' => ['id_payplug_queue' => 1],
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
        $this->repository->shouldReceive([
            'getFirstNotTreatedEntry' => ['id_payplug_queue' => 1],
            'updateEntity' => true,
        ]);

        $this->assertSame(
            [
                'exists' => ['id_payplug_queue' => 1],
                'result' => true,
            ],
            $this->action->updateAction($this->id_cart)
        );
    }

    public function testWhenQueueIsUpdatedAndTreated()
    {
        $this->repository->shouldReceive([
            'updateEntity' => true,
        ]);

        $this->repository->shouldReceive('getFirstNotTreatedEntry')
            ->once()
            ->andReturn(['id_payplug_queue' => 1]);

        $this->repository->shouldReceive('getFirstNotTreatedEntry')
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
