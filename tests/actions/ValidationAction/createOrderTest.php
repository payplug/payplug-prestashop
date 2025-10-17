<?php

namespace PayPlug\tests\actions\ValidationAction;

/**
 * @group unit
 * @group action
 * @group validation_action
 *
 * @runTestsInSeparateProcesses
 */
class createOrderTest extends BaseValidationAction
{
    protected $order_action;
    protected $payment_repository;
    protected $stored_payment;

    public function setUp()
    {
        parent::setUp();
        $this->order_action = \Mockery::mock('OrderAction');
        $this->payment_repository = \Mockery::mock('PaymentRepository');
        $this->stored_payment = [
            'resource_id' => 'pay_azerty12346',
        ];
        $this->plugin->shouldReceive([
            'getOrderAction' => $this->order_action,
            'getPaymentRepository' => $this->payment_repository,
        ]);
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
            ],
            $this->action->createOrder($cart_id)
        );
    }

    public function testWhenPaymentCantBeRetrievedFromDataBase()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->createOrder($this->cart_id)
        );
    }

    public function testWhenLockCantBeSetted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->action->shouldReceive([
            'setLock' => false,
        ]);
        $this->assertSame(
            [
                'result' => true,
            ],
            $this->action->createOrder($this->cart_id)
        );
    }

    public function testWhenOrderCantBeCreated()
    {
        $this->order_action->shouldReceive([
            'createAction' => [
                'result' => false,
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->action->shouldReceive([
            'setLock' => true,
        ]);
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->action->createOrder($this->cart_id)
        );
    }

    public function testWhenOrderIsCreated()
    {
        $this->order_action->shouldReceive([
            'createAction' => [
                'result' => true,
                'id_order' => 42,
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->action->shouldReceive([
            'setLock' => true,
        ]);
        $this->assertSame(
            [
                'result' => true,
                'id_order' => 42,
            ],
            $this->action->createOrder($this->cart_id)
        );
    }
}
