<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\src\models\classes\Translation;
use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\RefundMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class refundActionTest extends BasePaymentAction
{
    private $resource_id;
    private $amount;
    private $id_customer;
    private $id_order;
    private $update_order_state;

    public function setUp()
    {
        parent::setUp();

        $this->resource_id = 'resource_id';
        $this->amount = 4242;
        $this->id_customer = 1;
        $this->id_order = 2;
        $this->update_order_state = false;

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });

        $this->plugin->shouldReceive([
            'getTranslationClass' => $this->translation,
        ]);
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsntValidString($resource_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.format',
            ],
            $this->action->refundAction($resource_id, $this->amount, $this->id_customer, $this->id_order, $this->update_order_state)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsntValidInteger($amount)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.format',
            ],
            $this->action->refundAction($this->resource_id, $amount, $this->id_customer, $this->id_order, $this->update_order_state)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_customer
     */
    public function testWhenGivenIdCustomerIsntValidInteger($id_customer)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.default',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $id_customer, $this->id_order, $this->update_order_state)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order
     */
    public function testWhenGivenIdOrderIsntValidInteger($id_order)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.default',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $id_order, $this->update_order_state)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $update_order_state
     */
    public function testWhenGivenUpdateOrderStateIsntValidInteger($update_order_state)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.default',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order, $update_order_state)
        );
    }

    public function testWhenStoredPaymentCantBeGetted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.default',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order, $this->update_order_state)
        );
    }

    public function testWhenAmountIsNotValidForToRefund()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'getRefundableAmount' => 99999,
        ]);
        $this->payment_validator->shouldReceive([
            'isRefundableAmount' => [
                'result' => false,
                'code' => 'format',
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.format',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order, $this->update_order_state)
        );
    }

    public function testWhenResourceCantBeRefunded()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'getRefundableAmount' => 99999,
            'refund' => [
                'code' => 500,
                'result' => false,
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isRefundableAmount' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.default',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order, $this->update_order_state)
        );
    }

    public function testWhenRefundNeedAReload()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'getRefundableAmount' => 99999,
            'getRefundedAmount' => 99999,
            'refund' => [
                'result' => true,
                'resource' => RefundMock::get(),
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isRefundableAmount' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_refund')
            ->andReturn('42');
        $order_adapter = \Mockery::mock('OrderAdapter');
        $order_adapter->shouldReceive([
            'get' => OrderMock::get(),
        ]);
        $validate_adapter = \Mockery::mock('Validate');
        $validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->plugin->shouldReceive([
            'getOrder' => $order_adapter,
            'getValidate' => $validate_adapter,
        ]);
        $this->order_class->shouldReceive([
            'updateOrderState' => true,
        ]);
        $this->action->shouldReceive([
            'renderRefundData' => 'render_refund_data',
            'renderTemplate' => 'render_template',
        ]);
        $this->assertSame(
            [
                'result' => true,
                'data' => 'render_refund_data',
                'template' => 'render_template',
                'message' => 'refund.success',
                'modal' => '',
                'reload' => true,
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order, true)
        );
    }

    public function testWhenRefundNeedAManualAction()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);
        $refund = new \stdClass();
        $refund->object = 'error';
        $this->payment_method->shouldReceive([
            'getRefundableAmount' => 99999,
            'getRefundedAmount' => 99999,
            'refund' => [
                'result' => true,
                'resource' => $refund,
            ],
        ]);
        $this->payment_validator->shouldReceive([
            'isRefundableAmount' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->action->shouldReceive([
            'renderRefundData' => 'render_refund_data',
            'renderTemplate' => 'render_template',
            'renderModalTemplate' => 'render_modal_template',
        ]);
        $this->assertSame(
            [
                'result' => true,
                'data' => 'render_refund_data',
                'template' => 'render_template',
                'message' => 'refund.success',
                'modal' => 'render_modal_template',
                'reload' => false,
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order, $this->update_order_state)
        );
    }
}
