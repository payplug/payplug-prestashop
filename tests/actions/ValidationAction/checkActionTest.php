<?php

namespace PayPlug\tests\actions\ValidationAction;

/**
 * @group unit
 * @group action
 * @group validation_action
 */
class checkActionTest extends BaseValidationAction
{
    protected $configClass;
    protected $links;
    protected $order_adapter;
    protected $queue_repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->links = [
            'error' => 'error_url',
            'cancel' => 'cancel_url',
            'confirm' => 'confirm_url',
        ];
        $this->action->shouldReceive([
            'getOrderLinks' => $this->links,
            'clearLock' => true,
        ]);

        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $this->queue_repository = \Mockery::mock('QueueRepository');

        $cart_adapter = \Mockery::mock('CartAdapter');
        $cart_adapter->shouldReceive([
            'get' => \PayPlug\tests\mock\CartMock::get(),
        ]);

        $validate_adapter = \Mockery::mock('ValidateAdapter');
        $validate_adapter->shouldReceive('validate')
            ->andReturn(true);

        $this->plugin->shouldReceive([
            'getCart' => $cart_adapter,
            'getOrder' => $this->order_adapter,
            'getQueueRepository' => $this->queue_repository,
            'getValidate' => $validate_adapter,
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
        $this->order_adapter->shouldReceive([
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
        $this->order_adapter->shouldReceive([
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
        $this->order_adapter->shouldReceive([
            'getIdByCartId' => false,
        ]);
        $this->queue_repository->shouldReceive([
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
        $this->order_adapter->shouldReceive([
            'getIdByCartId' => false,
        ]);
        $this->queue_repository->shouldReceive([
            'updateBy' => true,
        ]);
        $this->action->shouldReceive([
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
        $this->order_adapter->shouldReceive([
            'getIdByCartId' => false,
        ]);
        $this->queue_repository->shouldReceive([
            'updateBy' => true,
        ]);
        $this->action->shouldReceive([
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

    public function testWhenOrderCreationIsDeferred()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        // Returns false both for the top-of-method check and for checkAction()'s
        // re-check after a deferred createOrder() result: the order genuinely never
        // got created, so the error page redirect is the correct outcome.
        $this->order_adapter
            ->shouldReceive('getIdByCartId')
            ->once()
            ->andReturn(false);
        $this->order_adapter->shouldReceive([
            'getIdByCartId' => false,
        ]);
        $this->queue_repository->shouldReceive([
            'updateBy' => true,
        ]);
        $this->action->shouldReceive([
            'createOrder' => [
                'result' => true,
                'id_order' => 0,
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

    public function testWhenOrderCreationIsDeferredButOrderAlreadyExists()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        // False for the top-of-method check (so we proceed to createOrder()), then a
        // real order id for checkAction()'s re-check: an order was created concurrently
        // (e.g. by an IPN notification) while this deferred createOrder() call was
        // in flight, so it must not be reported as a failure to the customer.
        $this->order_adapter
            ->shouldReceive('getIdByCartId')
            ->once()
            ->andReturn(false);
        $this->order_adapter->shouldReceive([
            'getIdByCartId' => 42,
        ]);
        $this->queue_repository->shouldReceive([
            'updateBy' => true,
        ]);
        $this->action->shouldReceive([
            'createOrder' => [
                'result' => true,
                'id_order' => 0,
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
