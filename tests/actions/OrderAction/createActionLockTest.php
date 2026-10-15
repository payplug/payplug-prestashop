<?php

namespace PayPlug\tests\actions\OrderAction;

use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\CustomerMock;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group order_action
 */
class createActionLockTest extends BaseOrderAction
{
    private $id_cart;
    private $resource_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->id_cart = (int) CartMock::get()->id;
        $this->resource_id = 'pay_test_123';
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testLockIsTakenForTheCartAndReleasedWhenOrderAlreadyExists()
    {
        $this->arrangeUpToCart();
        $this->order_repository->shouldReceive([
            'getByIdCart' => [['id_order' => 1]],
        ]);
        $this->order_lock->shouldReceive('acquire')
            ->with($this->id_cart)
            ->once()
            ->andReturn(true);
        $this->order_lock->shouldReceive('release')
            ->with($this->id_cart)
            ->once()
            ->andReturn(true);

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Order already exists for the given cart',
            ],
            $this->action->createAction($this->resource_id, true)
        );
    }

    public function testExistenceCheckRunsAfterAcquire()
    {
        $this->arrangeUpToCart();
        $this->order_lock->shouldReceive('acquire')
            ->once()
            ->globally()
            ->ordered()
            ->andReturn(true);
        $this->order_repository->shouldReceive('getByIdCart')
            ->once()
            ->globally()
            ->ordered()
            ->andReturn([['id_order' => 1]]);
        $this->order_lock->shouldReceive('release')
            ->once()
            ->globally()
            ->ordered()
            ->andReturn(true);

        $this->assertTrue($this->action->createAction($this->resource_id, true)['result']);
    }

    public function testExistenceCheckBypassesTheSqlCache()
    {
        $this->arrangeUpToCart();
        $this->order_lock->shouldReceive([
            'acquire' => true,
            'release' => true,
        ]);
        $this->order_repository->shouldReceive('getByIdCart')
            ->with($this->id_cart, false)
            ->once()
            ->andReturn([['id_order' => 1]]);

        $this->assertTrue($this->action->createAction($this->resource_id, true)['result']);
    }

    public function testLockNotAcquiredNeverCreatesTheOrder()
    {
        $this->arrangeUpToCart();
        $this->order_lock->shouldReceive('acquire')
            ->once()
            ->andReturn(false);
        $this->order_lock->shouldNotReceive('release');
        $this->order_repository->shouldNotReceive('getByIdCart');
        $this->module->shouldNotReceive('validateOrder');

        $result = $this->action->createAction($this->resource_id, true);

        $this->assertSame(
            [
                'result' => false,
                'locked' => true,
                'message' => 'Order creation already in progress for the given cart',
            ],
            $result
        );
    }

    public function testLockIsNotTakenWhenPaymentIsNotPaidYet()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->getStoredPayment(),
        ]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => ['result' => false],
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => false]),
            ],
        ]);
        $this->order_lock->shouldNotReceive('acquire');

        $this->assertSame(
            [
                'result' => true,
                'message' => 'The payment is not paid yet.',
            ],
            $this->action->createAction($this->resource_id, true)
        );
    }

    public function testLockReleasedWhenValidateOrderThrows()
    {
        $this->arrangeUpToCart();
        $this->arrangeOrderCreation();
        $this->order_repository->shouldReceive([
            'getByIdCart' => [],
        ]);
        $this->module->shouldReceive('validateOrder')
            ->andThrow(new \Exception('boom'));
        $this->order_lock->shouldReceive('release')
            ->with($this->id_cart)
            ->once()
            ->andReturn(true);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Order cannot be validated: boom',
            ],
            $this->action->createAction($this->resource_id, true)
        );
    }

    public function testLockReleasedWhenValidateOrderRaisesAnError()
    {
        $this->arrangeUpToCart();
        $this->arrangeOrderCreation();
        $this->order_repository->shouldReceive([
            'getByIdCart' => [],
        ]);
        $this->module->shouldReceive('validateOrder')
            ->andThrow(new \TypeError('hook failure'));
        $this->order_lock->shouldReceive('release')
            ->with($this->id_cart)
            ->once()
            ->andReturn(true);

        $this->expectException(\TypeError::class);

        $this->action->createAction($this->resource_id, true);
    }

    public function testLockReleasedOnSuccess()
    {
        $this->arrangeUpToCart();
        $this->arrangeOrderCreation();
        $this->order_repository->shouldReceive('getByIdCart')
            ->once()
            ->andReturn([]);
        $this->order_repository->shouldReceive('getByIdCart')
            ->andReturn([['id_order' => 42]]);
        $this->module->shouldReceive('validateOrder')
            ->once()
            ->andReturn(true);
        $this->order_lock->shouldReceive('release')
            ->with($this->id_cart)
            ->once()
            ->andReturn(true);

        $this->assertSame(
            [
                'result' => true,
                'id_order' => 42,
                'message' => '',
            ],
            $this->action->createAction($this->resource_id, true)
        );
    }

    public function testTwoCallersCreateOneOrder()
    {
        $this->arrangeUpToCart();
        $this->arrangeOrderCreation();
        $orders = [];
        $this->order_repository->shouldReceive('getByIdCart')
            ->andReturnUsing(function () use (&$orders) {
                return $orders;
            });
        $validate_order_calls = 0;
        $this->module->shouldReceive('validateOrder')
            ->andReturnUsing(function () use (&$orders, &$validate_order_calls) {
                ++$validate_order_calls;
                $orders = [['id_order' => 42]];

                return true;
            });
        $this->arrangeSerialisingLock();

        $first = $this->action->createAction($this->resource_id, true);
        $second = $this->action->createAction($this->resource_id);

        $this->assertSame(1, $validate_order_calls);
        $this->assertSame(42, $first['id_order']);
        $this->assertSame(
            [
                'result' => true,
                'message' => 'Order already exists for the given cart',
            ],
            $second
        );
    }

    public function testCallerArrivingDuringOrderCreationDoesNotCreateASecondOrder()
    {
        $this->arrangeUpToCart();
        $this->arrangeOrderCreation();
        $orders = [];
        $concurrent = null;
        $this->order_repository->shouldReceive('getByIdCart')
            ->andReturnUsing(function () use (&$orders, &$concurrent) {
                // The other caller (e.g. the IPN) reaches createAction() while this one sits
                // between its existence check and validateOrder().
                $seen = $orders;
                if (null === $concurrent) {
                    $concurrent = false;
                    $concurrent = $this->action->createAction($this->resource_id);
                }

                return $seen;
            });
        $validate_order_calls = 0;
        $this->module->shouldReceive('validateOrder')
            ->andReturnUsing(function () use (&$orders, &$validate_order_calls) {
                ++$validate_order_calls;
                $orders = [['id_order' => 42]];

                return true;
            });
        $this->arrangeSerialisingLock();

        $first = $this->action->createAction($this->resource_id, true);
        $retry = $this->action->createAction($this->resource_id);

        $this->assertSame(1, $validate_order_calls);
        $this->assertSame(42, $first['id_order']);
        $this->assertFalse($concurrent['result']);
        $this->assertTrue($concurrent['locked']);
        $this->assertSame(
            [
                'result' => true,
                'message' => 'Order already exists for the given cart',
            ],
            $retry
        );
    }

    private function getStoredPayment()
    {
        return [
            'id_payplug_payment' => 42,
            'resource_id' => $this->resource_id,
            'is_live' => false,
            'method' => 'standard',
            'id_cart' => $this->id_cart,
            'cart_hash' => 'cart-hash-test',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];
    }

    private function arrangeUpToCart()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->getStoredPayment(),
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true]),
            ],
        ]);
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
    }

    private function arrangeOrderCreation()
    {
        $order = \Mockery::mock('Order');
        $order->id = 42;
        $order->shouldReceive([
            'getOrderPayments' => [],
            'addOrderPayment' => true,
        ]);

        $this->customer_adapter->shouldReceive([
            'get' => CustomerMock::get(),
        ]);
        $this->payment_method->shouldReceive([
            'getOrderTab' => [
                'order_state' => 2,
                'amount' => 4242,
                'module_name' => 'Payplug',
            ],
            'postProcessOrder' => true,
        ]);
        $this->payment_validator->shouldReceive([
            'canSaveCard' => false,
        ]);
        $this->module->currentOrder = 42;
        $this->order_adapter->shouldReceive([
            'get' => $order,
        ]);
    }

    /**
     * Lock fake: a caller arriving while the lock is held does not get it,
     * as when GET_LOCK times out behind the holder.
     */
    private function arrangeSerialisingLock()
    {
        $held = false;
        $this->order_lock->shouldReceive('acquire')
            ->andReturnUsing(function () use (&$held) {
                if ($held) {
                    return false;
                }
                $held = true;

                return true;
            });
        $this->order_lock->shouldReceive('release')
            ->andReturnUsing(function () use (&$held) {
                $held = false;

                return true;
            });
    }
}
