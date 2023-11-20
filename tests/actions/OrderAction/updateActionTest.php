<?php

namespace PayPlug\tests\actions\OrderAction;

use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group order_action
 *
 * @runTestsInSeparateProcesses
 */
class updateActionTest extends BaseOrderAction
{
    public function setUp()
    {
        parent::setUp();

        $this->order_class
            ->shouldReceive([
                'getOrderStates' => [
                    'cancelled' => '1',
                    'error' => '2',
                    'expired' => '3',
                    'outofstock_paid' => '4',
                    'paid' => '5',
                ],
            ]);
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
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenNoPaymentRetrieveFromDatabase()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [],
            ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t retrieve resource from database',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenNoPaymentRetrieveFromAPI()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);

        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => false,
                ],
            ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t retrieve resource from api',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenRetrievedPaymentIsUnpaid()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(['metadata' => ['Order' => 42]]),
                ],
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);

        $this->assertSame(
            [
                'result' => true,
                'message' => 'The payment is not paid yet.',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenRelatedOrderIsNotValid()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'is_paid' => true,
                        'metadata' => ['Order' => 42],
                    ]),
                ],
            ]);
        $this->order_adapter
            ->shouldReceive([
                'get' => OrderMock::get(),
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $mock) {
                return $mock != OrderMock::get();
            });

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Order cannot be loaded',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenCurrentStateIsUndefined()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'is_paid' => true,
                        'metadata' => ['Order' => 42],
                    ]),
                ],
            ]);
        $this->order_adapter
            ->shouldReceive([
                'get' => OrderMock::get(),
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_order_state_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => false,
                'setOrderState' => true,
            ]);

        $this->assertSame(
            [
                'result' => true,
                'id_order' => 42,
                'message' => 'No action required, order state type is undefined',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenCurrentStateRequiredNoAction()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'is_paid' => true,
                        'metadata' => ['Order' => 42],
                    ]),
                ],
            ]);
        $this->order_adapter
            ->shouldReceive([
                'get' => OrderMock::get(),
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_order_state_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => 'paid',
            ]);

        $this->assertSame(
            [
                'result' => true,
                'id_order' => 42,
                'message' => 'No action required, order state type is paid',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenOrderCannotBeUpdateWithErrorStatus()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'is_paid' => true,
                        'metadata' => ['Order' => 42],
                    ]),
                ],
            ]);
        $this->order_adapter
            ->shouldReceive([
                'get' => OrderMock::get(),
            ]);
        $this->order_class
            ->shouldReceive([
                'getOrderStateFromResource' => [
                    'result' => false,
                    'status' => 'error',
                ],
                'updateOrderState' => false,
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_order_state_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => 'pending',
            ]);

        $this->assertSame(
            [
                'result' => false,
                'id_order' => 42,
                'message' => 'Can\'t update order state',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenOrderIsUpdatedWithErrorStatus()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'is_paid' => true,
                        'metadata' => ['Order' => 42],
                    ]),
                ],
            ]);
        $this->order_adapter
            ->shouldReceive([
                'get' => OrderMock::get(),
            ]);
        $this->order_class
            ->shouldReceive([
                'getOrderStateFromResource' => [
                    'result' => false,
                    'status' => 'error',
                ],
                'updateOrderState' => true,
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_order_state_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => 'pending',
            ]);

        $this->assertSame(
            [
                'result' => true,
                'id_order' => 42,
                'message' => 'Order state will be: 2',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenOrderPaymentCannotBeAdded()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'is_paid' => true,
                        'metadata' => ['Order' => 42],
                    ]),
                ],
            ]);
        $order = \Mockery::mock('Order');
        $order->id = 42;
        $order->id_cart = 42;
        $order->current_state = 42;
        $order->shouldReceive([
            'getOrderPayments' => [],
            'addOrderPayment' => false,
        ]);

        $this->order_adapter
            ->shouldReceive([
                'get' => $order,
            ]);
        $this->order_class
            ->shouldReceive([
                'getOrderStateFromResource' => [
                    'result' => true,
                    'status' => 'paid',
                ],
                'updateOrderState' => true,
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_order_state_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => 'pending',
            ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can set order payment for given order',
            ],
            $this->action->updateAction($resource_id)
        );
    }

    public function testWhenOrderIsUpdated()
    {
        $resource_id = 'pay_azerty123456';

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'is_paid' => true,
                        'metadata' => ['Order' => 42],
                    ]),
                ],
            ]);
        $order = \Mockery::mock('Order');
        $order->id = 42;
        $order->id_cart = 42;
        $order->current_state = 42;
        $order->shouldReceive([
            'getOrderPayments' => [
                [
                    'order_payment' => 'not empty',
                ],
            ],
        ]);

        $this->order_adapter
            ->shouldReceive([
                'get' => $order,
            ]);
        $this->order_class
            ->shouldReceive([
                'getOrderStateFromResource' => [
                    'result' => true,
                    'status' => 'paid',
                ],
                'updateOrderState' => true,
            ]);
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);
        $this->payment_validator
            ->shouldReceive([
                'isInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->payplug_order_state_repository
            ->shouldReceive([
                'getTypeByIdOrderState' => 'pending',
            ]);

        $this->assertSame(
            [
                'result' => true,
                'id_order' => 42,
                'message' => 'Order update with state: 5',
            ],
            $this->action->updateAction($resource_id)
        );
    }
}
