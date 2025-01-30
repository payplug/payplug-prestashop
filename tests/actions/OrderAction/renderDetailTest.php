<?php

namespace PayPlug\tests\actions\OrderAction;

use PayPlug\tests\mock\OrderMock;

/**
 * @group unit
 * @group action
 * @group order_action
 *
 * @runTestsInSeparateProcesses
 */
class renderDetailTest extends BaseOrderAction
{
    protected $constant;

    public function setUp()
    {
        parent::setUp();

        $this->constant = \Mockery::mock('Constant');
        $this->plugin->shouldReceive([
            'getConstant' => $this->constant,
        ]);
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $resource_id
     * @param mixed $order_id
     */
    public function testWhenGivenOrderIdIsInvalidStringFormat($order_id)
    {
        $this->assertSame(
            [],
            $this->action->renderDetail($order_id)
        );
    }

    public function testWhenRelatedOrderCantBeGot()
    {
        $order_id = 42;
        $this->order_adapter->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->assertSame(
            [],
            $this->action->renderDetail($order_id)
        );
    }

    public function testWhenRelatedOrderIsntRelatedToCurrentModule()
    {
        $order_id = 42;
        $order = OrderMock::get();
        $order->module = 'unknow_module';
        $this->order_adapter->shouldReceive([
            'get' => $order,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->assertSame(
            [],
            $this->action->renderDetail($order_id)
        );
    }

    public function testWhenCantRetrievePaymentFromDatabase()
    {
        $order_id = 42;
        $order = OrderMock::get();
        $order->module = 'payplug';
        $this->order_adapter->shouldReceive([
            'get' => $order,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->assertSame(
            [],
            $this->action->renderDetail($order_id)
        );
    }

    public function testWhenUndefinedOrderStateIsInHistory()
    {
        $adminClass = \Mockery::mock('AdminClass');
        $adminClass->shouldReceive([
            'getAdminAjaxUrl' => '/',
        ]);
        $this->dependencies->adminClass = $adminClass;

        $orderClass = \Mockery::mock('OrderClass');
        $orderClass->shouldReceive([
            'getUndefinedOrderHistory' => [],
        ]);
        $this->dependencies->orderClass = $orderClass;

        $order_id = 42;
        $order = OrderMock::get();
        $order->module = 'payplug';
        $payment = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty12345',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => '4cbaebd7df677672ac3d571012ea0498129a5314271b0c38603c66425560bf43',
            'schedules' => '',
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this->payment_method->shouldReceive([
            'getOrderTab' => [],
            'getResourceDetail' => [
                'mode' => 'live',
            ],
        ]);
        $this->configuration_class->shouldReceive('getValue')
            ->with('order_state_pending')
            ->andReturn('42');
        $this->constant->shouldReceive('get')
            ->with('__PS_BASE_URI__')
            ->andReturn('/');
        $this->order_adapter->shouldReceive([
            'get' => $order,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => $payment,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $expected = [
            'logo_url' => '/modules/payplug/views/img/payplug.svg',
            'admin_ajax_url' => '/',
            'order' => $order,
            'refund' => false,
            'refunded' => false,
            'update' => true,
            'payment' => [
                'mode' => 'live',
            ],
        ];

        $this->assertSame(
            $expected,
            $this->action->renderDetail($order_id)
        );
    }
}
