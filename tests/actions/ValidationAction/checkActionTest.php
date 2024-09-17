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
    protected $configClass;
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

        $this->configClass = \Mockery::mock('ConfigClass');
        $this->dependencies->configClass = $this->configClass;
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $cart_id
     */
    public function testWhenGivenCartIdIsInvalidIntegerFormat($cart_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->links['error'],
            ],
            $this->action->checkAction($cart_id)
        );
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
            $this->action->checkAction($this->cart_id, $last_try)
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
            $this->action->checkAction($this->cart_id)
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
            $this->action->checkAction($this->cart_id)
        );
    }

    public function testWhenCurrentQueueCantBeUpdated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
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
            $this->action->checkAction($this->cart_id, true)
        );
    }

    public function testWhenCurrentLockCantBeUpdated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->order_adapter->shouldReceive([
            'getIdByCartId' => false,
        ]);

        $lock_repository = \Mockery::mock('LockRepository');
        $lock_repository->shouldReceive([
            'deleteBy' => false,
        ]);
        $this->plugin->shouldReceive([
            'getLockRepository' => $lock_repository,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'action' => 'redirect',
                'redirected_url' => $this->links['error'],
            ],
            $this->action->checkAction($this->cart_id, true)
        );
    }

    public function testWhenOrderCantBeCreated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
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
            $this->action->checkAction($this->cart_id, true)
        );
    }

    public function testWhenOrderIsCreated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
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
            $this->action->checkAction($this->cart_id, true)
        );
    }
}
