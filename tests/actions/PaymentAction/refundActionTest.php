<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\src\models\classes\Translation;
use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\mock\RefundMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 */
class refundActionTest extends BasePaymentAction
{
    public $resource_id;
    public $amount;
    public $id_customer;
    public $id_order;

    public function setUp(): void
    {
        parent::setUp();

        $this->resource_id = 'resource_id';
        $this->amount = 4242;
        $this->id_customer = 1;
        $this->id_order = 2;

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
            $this->action->refundAction($resource_id, $this->amount, $this->id_customer, $this->id_order)
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
            $this->action->refundAction($this->resource_id, $amount, $this->id_customer, $this->id_order)
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
            $this->action->refundAction($this->resource_id, $this->amount, $id_customer, $this->id_order)
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
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $id_order)
        );
    }

    public function testWhenStoredPaymentCantBeGot()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.default',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order)
        );
    }

    /**
     * @dataProvider unretrievableCurrencyProvider
     *
     * @param array $retrieve
     */
    public function testWhenPaymentCurrencyCantBeRetrieved($retrieve)
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
            'retrieve' => $retrieve,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'refund.error.default',
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order)
        );
        $this->payment_method->shouldNotHaveReceived('refund');
        $this->logger->shouldHaveReceived('addLog')
            ->with('PaymentAction::getResourceCurrency - The resource currency cannot be retrieved.', 'error')
            ->once();
    }

    public function unretrievableCurrencyProvider()
    {
        return [
            'payment cannot be retrieved' => [['result' => false]],
            'payment without currency' => [[
                'result' => true,
                'resource' => PaymentMock::getStandard(['currency' => null]),
            ]],
        ];
    }

    public function testWhenRelatedOrderIsntValidRefundDataIsRenderedInThePaymentCurrency()
    {
        $this->context->get()->currency->iso_code = 'EUR';
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['currency' => 'JPY']),
            ],
            'getRefundableAmount' => 99999,
            'getRefundedAmount' => 4242,
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
        $order_adapter = \Mockery::mock('OrderAdapter');
        $order_adapter->shouldReceive([
            'get' => null,
        ]);
        $validate_adapter = \Mockery::mock('Validate');
        $validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->plugin->shouldReceive([
            'getOrder' => $order_adapter,
            'getValidate' => $validate_adapter,
        ]);
        $this->action->shouldReceive('renderRefundData')
            ->with(4242, 99999, 'JPY')
            ->andReturn('render_refund_data');
        $this->action->shouldReceive([
            'renderTemplate' => 'render_template',
        ]);

        $this->assertSame(
            [
                'result' => true,
                'data' => 'render_refund_data',
                'template' => 'render_template',
                'message' => 'refund.success',
                'modal' => '',
                'reload' => false,
            ],
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order)
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['currency' => 'JPY']),
            ],
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
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order)
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['currency' => 'JPY']),
            ],
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
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order)
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['currency' => 'JPY']),
            ],
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('order_state_partial_refund')
            ->andReturn('43');
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
        $this->action->shouldReceive('renderRefundData')
            ->with(99999, 99999, 'JPY')
            ->andReturn('render_refund_data');
        $this->action->shouldReceive([
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['currency' => 'JPY']),
            ],
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
        $this->action->shouldReceive('renderRefundData')
            ->with(99999, 99999, 'JPY')
            ->andReturn('render_refund_data');
        $this->action->shouldReceive([
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
            $this->action->refundAction($this->resource_id, $this->amount, $this->id_customer, $this->id_order)
        );
    }

    public function testRefundAfterAmountConversionRetrievesThePaymentOnce()
    {
        $amount_helper = \Mockery::mock('AmountHelper');
        $amount_helper->shouldReceive('convertAmount')->andReturn(4242);
        $this->module->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($amount_helper);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => $this->resource_id,
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
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['currency' => 'JPY']),
            ],
            'getRefundableAmount' => 99999,
            'getRefundedAmount' => 0,
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

        $amount = $this->action->getRefundAmountInCents('4242', $this->resource_id);
        $this->action->refundAction($this->resource_id, $amount, $this->id_customer, $this->id_order);

        $this->payment_method->shouldHaveReceived('retrieve')->with($this->resource_id)->once();
        $this->action->shouldHaveReceived('renderRefundData')->with(0, 99999, 'JPY')->once();
        $this->addToAssertionCount(1);
    }
}
