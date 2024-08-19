<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class abortActionTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsntValidString($resource_id)
    {
        $order_id = 42;
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsntValidInteger($order_id)
    {
        $resource_id = 'inst_azerty';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $order_id must be a non null integer.',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    public function testWhenInstallmentCannotBeAbort()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $api_class = \Mockery::mock('ApiClass');
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'abortInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies->apiClass = $api_class;

        $this->configuration
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn('0');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn('sk_live_azerty234567');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('test_api_key')
            ->andReturn('sk_test_azerty234567');

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t abort the payment.',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    public function testWhenInstallmentCannotBeRetrieveAfterAbort()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
        ];
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'abortInstallment' => $resource,
                'retrieveInstallment' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies->apiClass = $api_class;

        $this->configuration
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn('0');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('live_api_key')
            ->andReturn('sk_live_azerty234567');
        $this->configuration
            ->shouldReceive('getValue')
            ->with('test_api_key')
            ->andReturn('sk_test_azerty234567');

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t retrieve the aborted payment.',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    public function testWhenRelatedOrderIsntValid()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
        ];
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'abortInstallment' => $resource,
                'retrieveInstallment' => $resource,
            ]);
        $this->dependencies->apiClass = $api_class;

        $order = \Mockery::mock('Order');
        $order
            ->shouldReceive([
                'get' => OrderMock::get(),
            ]);

        $validate = \Mockery::mock('Validate');
        $validate
            ->shouldReceive([
                'validate' => false,
            ]);

        $this->plugin
            ->shouldReceive([
                'getOrder' => $order,
                'getValidate' => $validate,
            ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'The related Order object is not valid.',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    public function testWhenDataBaseCantBeUpdated()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
        ];
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'abortInstallment' => $resource,
                'retrieveInstallment' => $resource,
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(),
                ],
            ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method
            ->shouldReceive([
                'getPaymentStatus' => [
                    'id_status' => 2,
                    'code' => 'paid',
                ],
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $this->dependencies->apiClass = $api_class;

        $order_state = 42;
        $order_obj = \Mockery::mock('OrderObj');
        $order_obj->id = 42;
        $order_obj->id_cart = 42;
        $order_obj
            ->shouldReceive([
                'getCurrentState' => $order_state,
            ]);

        $order = \Mockery::mock('Order');
        $order
            ->shouldReceive([
                'get' => $order_obj,
            ]);

        $validate = \Mockery::mock('Validate');
        $validate
            ->shouldReceive([
                'validate' => true,
            ]);

        $configuration = \Mockery::mock('Configuration');
        $configuration
            ->shouldReceive('get')
            ->with('PS_OS_CANCELED')
            ->andReturn($order_state);

        $this->plugin
            ->shouldReceive([
                'getOrder' => $order,
                'getValidate' => $validate,
                'getConfiguration' => $configuration,
            ]);

        $this->payment_repository
            ->shouldReceive([
                'getBy' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'inst_azerty12345',
                    'method' => 'installmnent',
                    'id_cart' => 42,
                    'cart_hash' => '4cbaebd7df677672ac3d571012ea0498129a5314271b0c38603c66425560bf43',
                    'schedules' => '[{"id_payment":"pay_70Z8nKy6nWiZU6S77KFolE","step":"1\/2","amount":1150,"status":8,"scheduled_date":"2023-11-14","pay_id":"pay_70Z8nKy6nWiZU6S77KFolE"},{"id_payment":"","step":"1\/2","amount":1148,"status":6,"scheduled_date":"2023-12-14","pay_id":""}]',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
                'updateBy' => false,
            ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => '',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    public function testWhenDataBaseIsUpdated()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
        ];
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'abortInstallment' => $resource,
                'retrieveInstallment' => $resource,
                'retrievePayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(),
                ],
            ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method
            ->shouldReceive([
                'getPaymentStatus' => [
                    'id_status' => 2,
                    'code' => 'paid',
                ],
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $this->dependencies->apiClass = $api_class;

        $order_state = 42;
        $order_obj = \Mockery::mock('OrderObj');
        $order_obj->id = 42;
        $order_obj->id_cart = 42;
        $order_obj
            ->shouldReceive([
                'getCurrentState' => $order_state,
            ]);

        $order = \Mockery::mock('Order');
        $order
            ->shouldReceive([
                'get' => $order_obj,
            ]);

        $validate = \Mockery::mock('Validate');
        $validate
            ->shouldReceive([
                'validate' => true,
            ]);

        $configuration = \Mockery::mock('Configuration');
        $configuration
            ->shouldReceive('get')
            ->with('PS_OS_CANCELED')
            ->andReturn($order_state);

        $this->plugin
            ->shouldReceive([
                'getOrder' => $order,
                'getValidate' => $validate,
                'getConfiguration' => $configuration,
            ]);

        $this->payment_repository
            ->shouldReceive([
                'getBy' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'inst_azerty12345',
                    'method' => 'installmnent',
                    'id_cart' => 42,
                    'cart_hash' => '4cbaebd7df677672ac3d571012ea0498129a5314271b0c38603c66425560bf43',
                    'schedules' => '[{"id_payment":"pay_70Z8nKy6nWiZU6S77KFolE","step":"1\/2","amount":1150,"status":8,"scheduled_date":"2023-11-14","pay_id":"pay_70Z8nKy6nWiZU6S77KFolE"},{"id_payment":"","step":"1\/2","amount":1148,"status":6,"scheduled_date":"2023-12-14","pay_id":""}]',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
                'updateBy' => true,
            ]);

        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }
}
