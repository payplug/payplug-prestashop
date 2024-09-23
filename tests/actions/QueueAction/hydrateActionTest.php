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
        $this->repository
            ->shouldReceive([
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
        $this->repository
            ->shouldReceive([
                'getFirstNotTreatedEntry' => false,
                'createEntity' => true,
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
        $this->repository
            ->shouldReceive([
                'getFirstNotTreatedEntry' => true,
                'createEntity' => true,
            ]);
        $this->assertSame(
            [
                'exists' => true,
                'result' => true,
            ],
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }

    public function testWhenQueueExistsAndExpired()
    {
        $this->repository
            ->shouldReceive('getFirstNotTreatedEntry')
            ->once()
            ->andReturn([
                'id_payplug_queue' => 7,
                'id_cart' => $this->id_cart,
                'resource_id' => $this->resource_id,
                'type' => $this->type,
                'date_add' => '2222-09-23 16:28:11',
                'date_upd' => '2222-09-23 16:28:11',
                'treated' => false,
            ]);
        $this->repository
            ->shouldReceive([
                'getFirstNotTreatedEntry' => [
                    'id_payplug_queue' => 7,
                    'id_cart' => $this->id_cart,
                    'resource_id' => $this->resource_id,
                    'type' => $this->type,
                    'date_add' => '2024-09-23 16:28:11',
                    'date_upd' => '2024-09-23 16:28:11',
                    'treated' => false,
                ],
                'updateEntity' => true,
                'createEntity' => true,
                'hydrateAction' => [
                    'exists' => true,
                    'result' => true,
                ],
            ]);
        $this->assertSame(
            [
                'exists' => true,
                'result' => true,
            ],
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }

    public function testWhenQueueExistsAndFailUpdate()
    {
        $this->repository
            ->shouldReceive('getFirstNotTreatedEntry')
            ->once()
            ->andReturn([
                'id_payplug_queue' => 7,
                'id_cart' => $this->id_cart,
                'resource_id' => $this->resource_id,
                'type' => $this->type,
                'date_add' => '2222-09-23 16:28:11',
                'date_upd' => '2222-09-23 16:28:11',
                'treated' => false,
            ]);
        $this->repository
            ->shouldReceive([
                'getFirstNotTreatedEntry' => [
                    'id_payplug_queue' => 7,
                    'id_cart' => $this->id_cart,
                    'resource_id' => $this->resource_id,
                    'type' => $this->type,
                    'date_add' => '2024-09-23 16:28:11',
                    'date_upd' => '2024-09-23 16:28:11',
                    'treated' => false,
                ],
                'updateEntity' => false,
                'createEntity' => true,
                'hydrateAction' => [
                    'exists' => true,
                    'result' => true,
                ],
            ]);
        $this->assertSame(
            [
                'exists' => true,
                'result' => true,
            ],
            $this->action->hydrateAction($this->id_cart, $this->resource_id, $this->type)
        );
    }
}
