<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class captureActionTest extends BasePaymentAction
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
            $this->action->captureAction($resource_id, $order_id)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsntValidInteger($order_id)
    {
        $resource_id = 'pay_azerty12345';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $order_id must be a non null integer.',
            ],
            $this->action->captureAction($resource_id, $order_id)
        );
    }

    public function testWhenPaymentCannotBeCapture()
    {
        $order_id = 42;
        $resource_id = 'pay_azerty12345';
        $api_class = \Mockery::mock('ApiClass');
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'capturePayment' => [
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
                'message' => 'Can\'t capture the payment.',
            ],
            $this->action->captureAction($resource_id, $order_id)
        );
    }

    public function testWhenRelatedOrderIsntValid()
    {
        $order_id = 42;
        $resource_id = 'pay_azerty12345';
        $api_class = \Mockery::mock('ApiClass');
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'capturePayment' => $resource,
            ]);
        $this->dependencies->apiClass = $api_class;

        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('2');

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
            $this->action->captureAction($resource_id, $order_id)
        );
    }

    public function testWhenLockCantBeCreated()
    {
        $order_id = 42;
        $resource_id = 'pay_azerty12345';
        $api_class = \Mockery::mock('ApiClass');
        $cart_class = \Mockery::mock('CartClass');

        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'capturePayment' => $resource,
            ]);
        $cart_class
            ->shouldReceive([
                'createLockFromCartId' => false,
            ]);
        $this->dependencies->apiClass = $api_class;
        $this->dependencies->cartClass = $cart_class;

        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn('2');

        $order = \Mockery::mock('Order');
        $order
            ->shouldReceive([
                'get' => OrderMock::get(),
            ]);

        $validate = \Mockery::mock('Validate');
        $validate
            ->shouldReceive([
                'validate' => true,
            ]);

        $this->plugin
            ->shouldReceive([
                'getOrder' => $order,
                'getValidate' => $validate,
            ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'An error occured on lock creation',
            ],
            $this->action->captureAction($resource_id, $order_id)
        );
    }

    public function testWhenCaptureIsComplete()
    {
        $order_id = 42;
        $resource_id = 'pay_azerty12345';
        $api_class = \Mockery::mock('ApiClass');
        $cart_class = \Mockery::mock('CartClass');

        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $api_class
            ->shouldReceive([
                'setSecretKey' => true,
                'capturePayment' => $resource,
            ]);
        $cart_class
            ->shouldReceive([
                'createLockFromCartId' => true,
            ]);
        $this->dependencies->apiClass = $api_class;
        $this->dependencies->cartClass = $cart_class;

        $order_state = 2;
        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_paid')
            ->andReturn($order_state);
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

        $lock = \Mockery::mock('Lock');
        $lock
            ->shouldReceive([
                'deleteLock' => true,
            ]);

        $this->plugin
            ->shouldReceive([
                'getLockRepository' => $lock,
                'getOrder' => $order,
                'getValidate' => $validate,
            ]);

        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->action->captureAction($resource_id, $order_id)
        );
    }
}
