<?php

namespace PayPlug\tests\actions\ValidationAction;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group action
 * @group validation_action
 */
class checkActionLockedTest extends BaseValidationAction
{
    protected $links;
    protected $order_action;
    protected $order_adapter;
    protected $payment_repository;

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
        ]);
        $this->action->shouldReceive('clearLock')
            ->andReturn(true)
            ->byDefault();

        $this->order_adapter = \Mockery::mock('OrderAdapter');
        $queue_repository = \Mockery::mock('QueueRepository');
        $queue_repository->shouldReceive([
            'updateBy' => true,
        ]);

        $cart_adapter = \Mockery::mock('CartAdapter');
        $cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);

        $validate_adapter = \Mockery::mock('ValidateAdapter');
        $validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->order_action = \Mockery::mock('OrderAction');
        $this->payment_repository = \Mockery::mock('PaymentRepository');
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => 'pay_test_123',
            ],
        ]);

        $this->plugin->shouldReceive([
            'getCart' => $cart_adapter,
            'getOrder' => $this->order_adapter,
            'getOrderAction' => $this->order_action,
            'getPaymentRepository' => $this->payment_repository,
            'getQueueRepository' => $queue_repository,
            'getValidate' => $validate_adapter,
        ]);

        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $config_class;
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testLockedAndNoOrderWaits()
    {
        $this->stubLockedCreateOrder();
        $this->order_adapter->shouldReceive('getIdByCartId')
            ->twice()
            ->andReturn(0);

        $this->assertSame(
            [
                'result' => true,
                'action' => 'wait',
                'redirected_url' => $this->links['error'],
            ],
            $this->action->checkAction($this->cart_id, true)
        );
    }

    public function testLockedButOrderExistsRedirectsToConfirmation()
    {
        $this->stubLockedCreateOrder();
        $this->order_adapter->shouldReceive('getIdByCartId')
            ->once()
            ->andReturn(0);
        $this->order_adapter->shouldReceive('getIdByCartId')
            ->andReturn(42);

        $this->assertSame(
            [
                'result' => true,
                'action' => 'redirect',
                'redirected_url' => $this->links['confirm'],
            ],
            $this->action->checkAction($this->cart_id, true)
        );
    }

    public function testClearLockStillCalledWhenLocked()
    {
        $this->stubLockedCreateOrder();
        $this->order_adapter->shouldReceive([
            'getIdByCartId' => 0,
        ]);
        $this->action->shouldReceive('clearLock')
            ->with($this->cart_id)
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->action->checkAction($this->cart_id, true)['result']);
    }

    public function testQueueHeldElsewhereAndNoOrderYetWaits()
    {
        $this->action->shouldReceive([
            'setLock' => false,
        ]);
        $this->order_action->shouldNotReceive('createAction');
        $this->order_adapter->shouldReceive('getIdByCartId')
            ->twice()
            ->andReturn(0);

        $this->assertSame(
            [
                'result' => true,
                'action' => 'wait',
                'redirected_url' => $this->links['error'],
            ],
            $this->action->checkAction($this->cart_id, true)
        );
    }

    public function testQueueHeldElsewhereButOrderExistsRedirectsToConfirmation()
    {
        $this->action->shouldReceive([
            'setLock' => false,
        ]);
        $this->order_action->shouldNotReceive('createAction');
        $this->order_adapter->shouldReceive('getIdByCartId')
            ->once()
            ->andReturn(0);
        $this->order_adapter->shouldReceive('getIdByCartId')
            ->andReturn(42);

        $this->assertSame(
            [
                'result' => true,
                'action' => 'redirect',
                'redirected_url' => $this->links['confirm'],
            ],
            $this->action->checkAction($this->cart_id, true)
        );
    }

    private function stubLockedCreateOrder()
    {
        $this->action->shouldReceive([
            'createOrder' => [
                'result' => true,
                'id_order' => 0,
                'locked' => true,
            ],
        ]);
    }
}
