<?php

namespace PayPlug\tests\actions\OrderAction;

use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\CustomerMock;
use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group order_action
 *
 * @runTestsInSeparateProcesses
 */
class createActionTest extends BaseOrderAction
{
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
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenNoPaymentRetrieveFromDatabase()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t retrieve resource from database',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenNoPaymentRetrieveFromAPI()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);

        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
        ]);

        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t retrieve resource from api',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenPaymentRetrievedHasFailure()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);

        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
        ]);

        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard([
                    'failure' => [
                        'code' => '401',
                        'message' => 'Non authorized',
                    ],
                ]),
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Resource failure: Non authorized',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenRelatedCartIsntValid()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);

        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
        ]);

        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);

        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);

        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $mock) {
                return $mock != CartMock::get();
            });

        $this->assertSame(
            [
                'result' => false,
                'message' => '$cart should be a valid Cart Object',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenRelatedCustomerIsntValid()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->order_repository
            ->shouldReceive([
                'getByIdCart' => [],
            ]);

        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
        ]);

        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);

        $this->customer_adapter->shouldReceive([
            'get' => CustomerMock::get(),
        ]);

        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);

        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $mock) {
                return $mock != CustomerMock::get();
            });

        $this->assertSame(
            [
                'result' => false,
                'message' => '$customer should be a valid Customer Object',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenOrderTabCannotBeGeneratedForRelatedPaymentMethod()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);

        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
        ]);

        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);

        $this->customer_adapter->shouldReceive([
            'get' => CustomerMock::get(),
        ]);

        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->order_repository
            ->shouldReceive([
                'getByIdCart' => [],
            ]);

        $this->payment_method->shouldReceive([
            'getOrderTab' => [],
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => '$order_tab must be an non empty array',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenOrderCreationThrowAnException()
    {
        $resource_id = 'pay_azerty123456';

        $card_action = \Mockery::mock('CardAction');
        $card_action->shouldReceive([
            'saveAction' => true,
        ]);

        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);
        $this->module->shouldReceive('validateOrder')
            ->andThrow('Exception', 'validateOrder() method throw exception', 500);
        $this->order_repository->shouldReceive([
            'getCurrentOrders' => [],
            'getByIdCart' => [],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
            'canSaveCard' => [
                'result' => false,
            ],
        ]);
        $this->plugin->shouldReceive([
            'getCardAction' => $card_action,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Order cannot be validated: validateOrder() method throw exception',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenOrderCreatedIsntValid()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_method->shouldReceive([
            'getOrderTab' => [
                'order_state' => 2,
                'amount' => 4242,
                'module_name' => 'Payplug',
            ],
        ]);

        $card_action = \Mockery::mock('CardAction');
        $card_action->shouldReceive([
            'saveAction' => true,
        ]);

        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->customer_adapter->shouldReceive([
            'get' => CustomerMock::get(),
        ]);
        $this->payment_method->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);
        $this->module->shouldReceive([
            'validateOrder' => true,
        ]);
        $this->module->currentOrder = 42;
        $this->order_adapter->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $this->order_repository->shouldReceive([
            'getCurrentOrders' => [],
            'getByIdCart' => [],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
            'canSaveCard' => [
                'result' => false,
            ],
        ]);
        $this->plugin->shouldReceive([
            'getCardAction' => $card_action,
        ]);
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $mock) {
                return $mock != OrderMock::get();
            });

        $this->assertSame(
            [
                'result' => false,
                'message' => '$order should be a valid Order Object',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenOrderCannotBePostProcessed()
    {
        $resource_id = 'pay_azerty123456';

        $card_action = \Mockery::mock('CardAction');
        $card_action->shouldReceive([
            'saveAction' => true,
        ]);

        $order = \Mockery::mock('Order');
        $order->id = 42;
        $order->shouldReceive([
            'getOrderPayments' => [],
            'addOrderPayment' => true,
        ]);

        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);
        $this->module->shouldReceive([
            'validateOrder' => true,
        ]);
        $this->module->currentOrder = 42;
        $this->order_adapter->shouldReceive([
            'get' => $order,
        ]);
        $this->order_repository->shouldReceive([
            'getCurrentOrders' => [],
            'getByIdCart' => [],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
            'canSaveCard' => [
                'result' => false,
            ],
        ]);
        $this->plugin->shouldReceive([
            'getCardAction' => $card_action,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'No order created for the given cart id',
            ],
            $this->action->createAction($resource_id)
        );
    }

    public function testWhenOrderIsCreatedAndTreated()
    {
        $resource_id = 'pay_azerty123456';
        $this->payment_method->shouldReceive([
            'getOrderTab' => [
                'order_state' => 2,
                'amount' => 4242,
                'module_name' => 'Payplug',
            ],
            'postProcessOrder' => true,
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);

        $card_action = \Mockery::mock('CardAction');
        $card_action->shouldReceive([
            'saveAction' => true,
        ]);

        $order = \Mockery::mock('Order');
        $order->id = 42;
        $order->shouldReceive([
            'getOrderPayments' => [],
            'addOrderPayment' => true,
        ]);

        $merchant_telemetry_action = \Mockery::mock('MerchantTelemetryAction');
        $merchant_telemetry_action->shouldReceive([
            'sendAction' => true,
        ]);

        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->customer_adapter->shouldReceive([
            'get' => CustomerMock::get(),
        ]);
        $this->module->shouldReceive([
            'validateOrder' => true,
        ]);
        $this->module->currentOrder = 42;
        $this->order_adapter->shouldReceive([
            'get' => $order,
        ]);
        $this->order_repository
            ->shouldReceive('getByIdCart')
            ->once()
            ->andReturn([]);
        $this->order_repository->shouldReceive([
            'getCurrentOrders' => [],
            'getByIdCart' => [
                [
                    'id_order' => 1,
                    'id_lang' => 1,
                    'id_customer' => 3,
                    'id_cart' => 42,
                    'id_currency' => 1,
                    'id_address_delivery' => 14,
                    'id_address_invoice' => 14,
                    'current_state' => 2,
                ],
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isInstallment' => [
                'result' => false,
            ],
            'canSaveCard' => [
                'result' => false,
            ],
        ]);
        $this->plugin->shouldReceive([
            'getCardAction' => $card_action,
            'getMerchantTelemetryAction' => $merchant_telemetry_action,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'id_order' => 42,
                'message' => '',
            ],
            $this->action->createAction($resource_id)
        );
    }
}
