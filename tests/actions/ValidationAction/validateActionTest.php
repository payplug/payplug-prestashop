<?php

namespace PayPlug\tests\actions\ValidationAction;

use PayPlug\tests\mock\CartMock;

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
    protected $cart_adapter;
    protected $cart_id;
    protected $links;
    protected $order;
    protected $order_id;
    protected $validate_adapter;

    public function setUp()
    {
        parent::setUp();

        $this->cart_adapter = \Mockery::mock('CartAdapter');

        $this->links = [
            'error' => 'error_url',
            'cancel' => 'cancel_url',
            'confirm' => 'confirm_url',
        ];
        $this->ps = 1;
        $this->order = \Mockery::mock('Order');
        $this->order_id = 42;
        $this->validate_adapter = \Mockery::mock('ValidateAdapter');
        $this->action->shouldReceive([
            'getOrderLinks' => $this->links,
        ]);
        $this->plugin->shouldReceive([
            'getCart' => $this->cart_adapter,
            'getOrder' => $this->order,
            'getValidate' => $this->validate_adapter,
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

    public function testWhenGivenCartIdIsNotValidObjectCartId()
    {
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'url' => $this->links['error'],
                'message' => 'Given cart obj isn\'t a valid object.',
            ],
            $this->action->validateAction($this->ps, $this->cart_id)
        );
    }

    public function testWhenGivenCartCustomerIdDoesntMatchWithContextCustomerId()
    {
        $cart = CartMock::get();
        $cart->id_customer = 42;
        $this->cart_adapter->shouldReceive([
            'get' => $cart,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'url' => $this->links['error'],
                'message' => 'Given cart customer id did not match with context customer id.',
            ],
            $this->action->validateAction($this->ps, $this->cart_id)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $ps
     */
    public function testWhenGivenPsIsInvalidIntegerFormat($ps)
    {
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
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
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
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
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
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
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
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

    public function testWhenNoOrdersAreCreatedAndNoErrorsOccured()
    {
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->order->shouldReceive([
            'getIdByCartId' => false,
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
