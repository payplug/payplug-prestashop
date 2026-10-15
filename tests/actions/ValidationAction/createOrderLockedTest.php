<?php

namespace PayPlug\tests\actions\ValidationAction;

/**
 * @group unit
 * @group action
 * @group validation_action
 */
class createOrderLockedTest extends BaseValidationAction
{
    protected $order_action;
    protected $payment_repository;

    public function setUp(): void
    {
        parent::setUp();
        $this->order_action = \Mockery::mock('OrderAction');
        $this->payment_repository = \Mockery::mock('PaymentRepository');
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => 'pay_test_123',
            ],
        ]);
        $this->plugin->shouldReceive([
            'getOrderAction' => $this->order_action,
            'getPaymentRepository' => $this->payment_repository,
        ]);
        $this->action->shouldReceive([
            'setLock' => true,
        ]);
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testLockedOrderActionIsNotAnError()
    {
        $this->order_action->shouldReceive('createAction')
            ->with('pay_test_123', true)
            ->once()
            ->andReturn([
                'result' => false,
                'locked' => true,
                'message' => 'Order creation already in progress for the given cart',
            ]);

        $this->assertSame(
            [
                'result' => true,
                'id_order' => 0,
                'locked' => true,
            ],
            $this->action->createOrder($this->cart_id)
        );
    }

    public function testRealFailureStillFails()
    {
        $this->order_action->shouldReceive([
            'createAction' => [
                'result' => false,
                'message' => 'Order cannot be validated: boom',
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->createOrder($this->cart_id)
        );
    }
}
