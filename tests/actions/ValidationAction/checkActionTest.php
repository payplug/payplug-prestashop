<?php

namespace PayPlug\tests\actions\ValidationAction;

/**
 * @group unit
 * @group action
 * @group validation_action
 *
 * @runTestsInSeparateProcesses
 */
class checkActionTest extends BaseValidationAction
{
    protected $links;
    protected $order_adapter;
    protected $queue_repository;

    public function setUp()
    {
        parent::setUp();
        $this->links = [
            'error' => 'error_url',
            'cancel' => 'cancel_url',
            'confirm' => 'confirm_url',
        ];
        $this->action
            ->shouldReceive([
                'getOrderLinks' => $this->links,
                'clearLock' => true,
            ]);

        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->queue_repository = \Mockery::mock('QueueRepository');
        $this->plugin->shouldReceive([
            'getOrder' => $this->order_adapter,
            'getQueueRepository' => $this->queue_repository,
        ]);
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $last_try
     */
    public function testWhenGivenLastTryIsInvalidBooleanFormat($last_try)
    {
        $this->assertSame(
            [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->links['error'],
            ],
            $this->action->checkAction($last_try)
        );
    }

    public function testWhenAnOrderIsRetrievedForAGivenCartId()
    {
        $this->order_adapter
            ->shouldReceive([
                'getIdByCartId' => 42,
            ]);

        $this->assertSame(
            [
                'result' => true,
                'action' => 'redirect',
                'redirected_url' => $this->links['confirm'],
            ],
            $this->action->checkAction()
        );
    }

    public function testWhenNoOrdersAreRetrievedForAGivenCartId()
    {
        $this->order_adapter
            ->shouldReceive([
                'getIdByCartId' => false,
            ]);

        $this->assertSame(
            [
                'result' => true,
                'action' => 'wait',
            ],
            $this->action->checkAction()
        );
    }

    public function testWhenCurrentQueueCantBeUpdated()
    {
        $this->order_adapter
            ->shouldReceive([
                'getIdByCartId' => false,
            ]);
        $this->queue_repository
            ->shouldReceive([
                'updateBy' => false,
            ]);

        $this->assertSame(
            [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->links['error'],
            ],
            $this->action->checkAction(true)
        );
    }

    public function testWhenOrderCantBeCreated()
    {
        $this->order_adapter
            ->shouldReceive([
                'getIdByCartId' => false,
            ]);
        $this->queue_repository
            ->shouldReceive([
                'updateBy' => true,
            ]);
        $this->action
            ->shouldReceive([
                'createOrder' => [
                    'result' => false,
                ],
            ]);

        $this->assertSame(
            [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->links['error'],
            ],
            $this->action->checkAction(true)
        );
    }

    public function testWhenOrderIsCreated()
    {
        $this->order_adapter
            ->shouldReceive([
                'getIdByCartId' => false,
            ]);
        $this->queue_repository
            ->shouldReceive([
                'updateBy' => true,
            ]);
        $this->action
            ->shouldReceive([
                'createOrder' => [
                    'result' => true,
                    'id_order' => 42,
                ],
            ]);

        $this->assertSame(
            [
                'result' => true,
                'action' => 'redirect',
                'redirected_url' => $this->links['confirm'],
            ],
            $this->action->checkAction(true)
        );
    }
}
