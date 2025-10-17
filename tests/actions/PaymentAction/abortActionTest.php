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
    public function testWhenDataBaseCantBeUpdated()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 42,
                    'date' => '1970-01-01 00:00:00',
                    'resource' => PaymentMock::getStandard(),
                ],
            ],
        ];
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => $resource_id,
                'method' => 'installment',
                'schedules' => '[{"id_payment":"pay_7goqcIHyGH6xd5mMjcCX3b","step":"1\/3","amount":766,"status":5,"scheduled_date":"2024-08-30","pay_id":"pay_7goqcIHyGH6xd5mMjcCX3b"},{"id_payment":"","step":"2\/3","amount":764,"status":7,"scheduled_date":"2024-09-29","pay_id":""}]',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'abort' => $resource,
            'retrieve' => $resource,
            'getPaymentStatus' => [
                'id_status' => 2,
                'code' => 'paid',
            ],
        ]);

        $this->order_class->shouldReceive([
            'updateOrderState' => true,
        ]);

        $order = \Mockery::mock('Order');
        $order->shouldReceive([
            'get' => OrderMock::get(),
        ]);

        $validate = \Mockery::mock('Validate');
        $validate->shouldReceive([
            'validate' => true,
        ]);

        $configuration = \Mockery::mock('Configuration');
        $configuration->shouldReceive('get')
            ->with('PS_OS_CANCELED')
            ->andReturn(42);

        $this->plugin->shouldReceive([
            'getOrder' => $order,
            'getValidate' => $validate,
            'getConfiguration' => $configuration,
        ]);

        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'inst_azerty12345',
                'is_live' => true,
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
        $payment_standard = PaymentMock::getStandard();
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 42,
                    'date' => '1970-01-01 00:00:00',
                    'resource' => $payment_standard,
                ],
            ],
        ];
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => $resource_id,
                'method' => 'installment',
                'schedules' => '[{"id_payment":"' . $payment_standard->id . '","step":"1\/1","amount":4242,"status":5,"scheduled_date":"1970-01-01","pay_id":"' . $payment_standard->id . '"}]',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'abort' => $resource,
            'retrieve' => $resource,
            'getPaymentStatus' => [
                'id_status' => 2,
                'code' => 'paid',
            ],
        ]);

        $order = \Mockery::mock('Order');
        $order->shouldReceive([
            'get' => OrderMock::get(),
        ]);

        $validate = \Mockery::mock('Validate');
        $validate->shouldReceive([
            'validate' => true,
        ]);

        $configuration = \Mockery::mock('Configuration');
        $configuration->shouldReceive('get')
            ->with('PS_OS_CANCELED')
            ->andReturn(42);

        $this->order_class->shouldReceive([
            'updateOrderState' => true,
        ]);

        $this->plugin->shouldReceive([
            'getOrder' => $order,
            'getValidate' => $validate,
            'getConfiguration' => $configuration,
        ]);

        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'inst_azerty12345',
                'is_live' => true,
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

    public function testWhenInstallmentCannotBeAbort()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $this->payment_method->shouldReceive([
            'abort' => [
                'result' => false,
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => $resource_id,
                'method' => 'installment',
            ],
        ]);

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
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 42,
                    'date' => '1970-01-01 00:00:00',
                    'resource' => PaymentMock::getStandard(),
                ],
            ],
        ];
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => $resource_id,
                'method' => 'installment',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'abort' => $resource,
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t retrieve the aborted payment.',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    public function testWhenNoStoredPaymentCantBeGot()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Can\'t abort the payment.',
            ],
            $this->action->abortAction($resource_id, $order_id)
        );
    }

    public function testWhenRelatedOrderIsntValid()
    {
        $order_id = 42;
        $resource_id = 'inst_azerty';
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 42,
                    'date' => '1970-01-01 00:00:00',
                    'resource' => PaymentMock::getStandard(),
                ],
            ],
        ];
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => $resource_id,
                'method' => 'installment',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'abort' => $resource,
            'retrieve' => $resource,
        ]);

        $order = \Mockery::mock('Order');
        $order->shouldReceive([
            'get' => OrderMock::get(),
        ]);

        $validate = \Mockery::mock('Validate');
        $validate->shouldReceive([
            'validate' => false,
        ]);

        $this->plugin->shouldReceive([
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
}
