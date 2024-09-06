<?php

namespace PayPlug\tests\actions\ValidationAction;

use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group action
 * @group validation_action
 *
 * @runTestsInSeparateProcesses
 */
class validateActionTest extends BaseValidationAction
{
    protected $ps;
    protected $cart_id;
    protected $links;
    protected $order;
    protected $order_id;

    public function setUp()
    {
        parent::setUp();

        $context = ContextMock::get();
        $this->links = [
            'error' => 'error_url',
            'cancel' => 'cancel_url',
            'confirm' => 'confirm_url',
        ];
        $this->ps = 1;
        $this->cart_id = $context->cart->id;
        $this->order = \Mockery::mock('Order');
        $this->order_id = 42;
        $this->action->shouldReceive([
            'getOrderLinks' => $this->links,
        ]);
        $this->plugin->shouldReceive([
            'getOrder' => $this->order,
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
                'url' => $this->links['error'],
                'message' => 'Invalid argument given, $cart_id must be a non null integer.',
            ],
            $this->action->validateAction($this->ps, $cart_id)
        );
    }

    public function testWhenGivenCartIdDoesntMatchWithContextCartId()
    {
        $cart_id = 4242;
        $this->assertSame(
            [
                'result' => false,
                'url' => $this->links['error'],
                'message' => 'Given cart id did not match with context cart id.',
            ],
            $this->action->validateAction($this->ps, $cart_id)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $ps
     */
    public function testWhenGivenPsIsInvalidIntegerFormat($ps)
    {
        $this->assertSame(
            [
                'result' => false,
                'url' => $this->links['error'],
                'message' => 'Invalid argument ps given',
            ],
            $this->action->validateAction($ps, $this->cart_id)
        );
    }

    public function testWhenGivenPsDoesntMatchWithExpectedValue()
    {
        $ps = 4242;
        $this->assertSame(
            [
                'result' => false,
                'url' => $this->links['error'],
                'message' => 'Invalid argument ps given',
            ],
            $this->action->validateAction($ps, $this->cart_id)
        );
    }

    public function testWhenGivenPsReturnCancelAction()
    {
        $ps = 2;
        $this->assertSame(
            [
                'result' => false,
                'url' => $this->links['cancel'],
                'message' => 'Order has been cancelled on PayPlug page',
            ],
            $this->action->validateAction($ps, $this->cart_id)
        );
    }

    public function testWhenOrderIsRetrieveForGivenCartId()
    {
        $this->order->shouldReceive([
            'getIdByCartId' => $this->order_id,
        ]);
        $this->assertSame(
            [
                'result' => true,
                'url' => $this->links['confirm'],
                'message' => 'Redirecting to order-confirmation page',
            ],
            $this->action->validateAction($this->ps, $this->cart_id)
        );
    }

    public function testWhenOrderCantBeCreated()
    {
        $this->order->shouldReceive([
            'getIdByCartId' => false,
        ]);
        $this->action->shouldReceive([
            'createOrder' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'url' => $this->links['error'],
                'message' => 'No stored payment get from given id cart.',
            ],
            $this->action->validateAction($this->ps, $this->cart_id)
        );
    }

    public function testWhenOrderIsCreated()
    {
        $this->order->shouldReceive([
            'getIdByCartId' => false,
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
                'url' => $this->links['confirm'],
                'message' => 'Redirecting to order-confirmation page.',
            ],
            $this->action->validateAction($this->ps, $this->cart_id)
        );
    }

    public function testWhenNoOrdersAreCreatedAndNoErrorsOccured()
    {
        $this->order->shouldReceive([
            'getIdByCartId' => false,
        ]);
        $this->action->shouldReceive([
            'createOrder' => [
                'result' => true,
            ],
        ]);
        $this->assertSame(
            [
                'result' => true,
                'message' => 'Show the validation template.',
            ],
            $this->action->validateAction($this->ps, $this->cart_id)
        );
    }
}
