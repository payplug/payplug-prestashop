<?php

namespace PayPlug\tests\actions\QueueAction;

/**
 * @group unit
 * @group action
 * @group queue_action
 *
 * @runTestsInSeparateProcesses
 */
class hydrateActionTest extends BaseQueueAction
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
            $this->action->hydrateAction($id_cart, $this->resource_id, $this->type)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsInvalidStringFormat($resource_id)
    {
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->hydrateAction($this->id_cart, $resource_id, $this->type)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $type
     */
    public function testWhenGivenTypeIsInvalidStringFormat($type)
    {
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $type)
        );
    }

    public function testWhenQueueCantBeCreated()
    {
        $this->repository->shouldReceive([
            'getFirstNotTreatedEntry' => false,
            'createEntity' => false,
        ]);
        $this->assertSame(
            [
                'exists' => false,
                'result' => false,
            ],
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }

    public function testWhenQueueIsCreatedAndQueueDoesntExists()
    {
        $this->repository->shouldReceive([
            'getFirstNotTreatedEntry' => false,
            'createEntity' => 72,
        ]);
        $this->assertSame(
            [
                'exists' => false,
                'result' => true,
            ],
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }

    public function testWhenQueueIsCreatedAndQueueExists()
    {
        $entry = [
            'id_payplug_queue' => 42,
            'date_add' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ];
        $this->repository->shouldReceive([
            'getFirstNotTreatedEntry' => $entry,
            'updateEntity' => false,
            'createEntity' => 72,
        ]);
        $this->assertSame(
            [
                'exists' => true,
                'result' => true,
            ],
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }

    public function testWhenFirstEntryQueueIsExpiredAndUpdated()
    {
        $entry = [
            'id_payplug_queue' => 42,
            'date_add' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ];
        $this->repository
            ->shouldReceive('getFirstNotTreatedEntry')
            ->once()
            ->andReturn($entry);
        $this->repository->shouldReceive([
            'getFirstNotTreatedEntry' => false,
            'updateEntity' => true,
            'createEntity' => 72,
        ]);
        $expected = [
            'exists' => false,
            'result' => true,
        ];
        $this->assertSame(
            $expected,
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }

    public function testWhenFirstEntryQueueIsExpiredAndCantBeUpdated()
    {
        $entry = [
            'id_payplug_queue' => 42,
            'date_add' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        ];
        $this->repository->shouldReceive([
            'getFirstNotTreatedEntry' => $entry,
            'updateEntity' => false,
            'createEntity' => 72,
        ]);
        $expected = [
            'exists' => true,
            'result' => true,
        ];
        $this->assertSame(
            $expected,
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }
}
