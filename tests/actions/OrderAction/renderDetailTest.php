<?php

namespace PayPlug\tests\actions\OrderAction;

use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\tests\mock\OrderMock;
use PayplugUnifiedCore\DataValues\OperationData;
use PayplugUnifiedCore\DataValues\PaymentOutcome;

/**
 * @group unit
 * @group action
 * @group order_action
 */
class renderDetailTest extends BaseOrderAction
{
    protected $constant;
    protected $operation_action;

    public function setUp(): void
    {
        parent::setUp();

        $this->constant = \Mockery::mock('Constant');
        $this->plugin->shouldReceive([
            'getConstant' => $this->constant,
        ]);
        $this->configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('0');
        $this->operation_action = \Mockery::mock('UnifiedPaymentAction');
        $this->operation_action->shouldReceive('resyncPendingOrder')->andReturn(false)->byDefault();
        $this->module->shouldReceive('getService')->with('payplug.action.unified_payment')->andReturn($this->operation_action);
    }

    public function tearDown(): void
    {
        \Mockery::close();
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
        $this->mockUnifiedServices(null, 0);

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

    public function testWhenNeitherARetailPaymentNorAPaidUhfOperationExists()
    {
        $order = OrderMock::get();
        $order->module = 'payplug';
        $this->order_adapter->shouldReceive(['get' => $order]);
        $this->payment_repository->shouldReceive(['getBy' => []]);
        $this->validate_adapter->shouldReceive(['validate' => true]);
        $this->configuration_class->shouldReceive('getValue')->with('order_state_pending')->andReturn('1');
        $this->mockUnifiedServices(null, 0);

        $this->assertSame([], $this->action->renderDetail(42));
    }

    /**
     * Same as the Retail payment methods' getResourceDetail(): a pending order is brought up to
     * date with its payment when opened, then the order page is reloaded to show its new state.
     */
    public function testReloadsTheOrderPageOnceAPendingUhfOrderIsResynced()
    {
        $this->mockUhfOrder('USD', '42');
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 0);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(0);
        $this->operation_action->shouldReceive('resyncPendingOrder')->once()->with(42)->andReturn(true);
        $link = \Mockery::mock('Link');
        $link->shouldReceive('getAdminLink')
            ->with('AdminOrders', true, [], ['vieworder' => 1, 'id_order' => 42])
            ->andReturn('admin/orders/42/view');
        $this->plugin->getContext()->get()->link = $link;
        $tools = \Mockery::mock('ToolsAdapter');
        $tools->shouldReceive('tool')->once()->with('redirectAdmin', 'admin/orders/42/view');
        $this->plugin->shouldReceive(['getTools' => $tools]);

        $this->assertSame('uhf', $this->action->renderDetail(42)['refund']['payment_type']);
    }

    public function testRendersThePendingUhfOrderWithoutReloadingWhenItsStateIsUnchanged()
    {
        $this->mockUhfOrder('USD', '42');
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 0);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(0);
        $this->operation_action->shouldReceive('resyncPendingOrder')->once()->with(42)->andReturn(false);
        $this->plugin->shouldReceive('getTools')->never();

        $result = $this->action->renderDetail(42);

        $this->assertSame('uhf', $result['refund']['payment_type']);
    }

    public function testStillRendersTheOrderAndLogsWhenThePendingOrderResyncFails()
    {
        $this->mockUhfOrder('USD', '42');
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 0);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(0);
        $this->operation_action->shouldReceive('resyncPendingOrder')->once()->andThrow(new \Exception('unified API down'));
        $this->plugin->shouldReceive('getTools')->never();

        $result = $this->action->renderDetail(42);

        $this->assertSame('uhf', $result['refund']['payment_type']);
        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::pattern('/order 42 could not be resynced.*unified API down/'), 'error')
            ->once();
    }

    public function testNeverResyncsAnOrderThatIsNotPending()
    {
        $this->mockUhfOrder('USD', '1');
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 0);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(0);
        $this->operation_action->shouldReceive('resyncPendingOrder')->never();

        $this->assertSame('uhf', $this->action->renderDetail(42)['refund']['payment_type']);
    }

    public function testComparesTheOrderWithTheTestModePendingStateInSandbox()
    {
        $order = OrderMock::get();
        $this->order_adapter->shouldReceive(['get' => $order]);
        $this->payment_repository->shouldReceive(['getBy' => []]);
        $this->validate_adapter->shouldReceive(['validate' => true]);
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $configuration_class->shouldReceive('getValue')->with('sandbox_mode')->andReturn('1');
        $configuration_class->shouldReceive('getValue')->with('order_state_pending_test')->andReturn('42');
        $this->setPrivateProperty('configuration', $configuration_class);
        $this->mockUnifiedServices(null, 0);
        $this->operation_action->shouldReceive('resyncPendingOrder')->once()->with(42)->andReturn(false);

        $this->assertSame([], $this->action->renderDetail(42));
    }

    public function testUhfOrderWithRefundableAmountExposesTheRefundFormInTheOrderCurrency()
    {
        $order = $this->mockUhfOrder();
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 1000);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(0);

        $result = $this->action->renderDetail(42);

        $this->assertSame($order, $result['order']);
        $this->assertSame('uhf', $result['refund']['payment_type']);
        $this->assertSame('op_pay', $result['refund']['id']);
        $this->assertSame(10.0, $result['refund']['refunded']);
        $this->assertSame(19.0, $result['refund']['available']);
        $this->assertSame('USD 19.00', $result['refund']['available_display']);
        $this->assertSame('USD 10.00', $result['refund']['refunded_display']);
        $this->assertSame('live', $result['refund']['mode']);
        $this->assertSame(7, $result['refund']['new_order_state']);
        $this->assertFalse($result['refund']['disabled']);
        $this->assertFalse($result['refunded']);
        $this->assertFalse($result['update']);
    }

    public function testUhfOrderInAZeroDecimalCurrencyExposesItsAmountsUnscaled()
    {
        $this->mockUhfOrder('JPY');
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 1000);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(0);

        $result = $this->action->renderDetail(42);

        $this->assertSame(1000.0, $result['refund']['refunded']);
        $this->assertSame(1900.0, $result['refund']['available']);
        $this->assertSame('JPY 1,900.00', $result['refund']['available_display']);
    }

    public function testUhfSuggestedAmountIsNotThousandsSeparatedForLargeAmounts()
    {
        $this->mockUhfOrder();
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 250000, '42'), 0);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(1500.0);

        $result = $this->action->renderDetail(42);

        $this->assertSame('1500.00', $result['refund']['suggested']);
    }

    public function testFullyRefundedUhfOrderShowsTheRefundedSummary()
    {
        $this->mockUhfOrder();
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 2900);

        $result = $this->action->renderDetail(42);

        $this->assertFalse($result['refund']);
        $this->assertSame(29.0, $result['refunded']);
        $this->assertSame('USD 29.00', $result['refunded_display']);
    }

    public function testRetailOrderWithoutPaidUhfOperationStillShowsTheRetailPanel()
    {
        // Same wiring as testWhenUndefinedOrderStateIsInHistory: a payplug_payment row exists.
        $adminClass = \Mockery::mock('AdminClass');
        $adminClass->shouldReceive(['getAdminAjaxUrl' => '/']);
        $this->dependencies->adminClass = $adminClass;
        $orderClass = \Mockery::mock('OrderClass');
        $orderClass->shouldReceive(['getUndefinedOrderHistory' => []]);
        $this->dependencies->orderClass = $orderClass;
        $order = OrderMock::get();
        $order->module = 'payplug';
        $this->payment_method->shouldReceive(['getResourceDetail' => ['mode' => 'live']]);
        $this->configuration_class->shouldReceive('getValue')->with('order_state_pending')->andReturn('42');
        $this->constant->shouldReceive('get')->with('__PS_BASE_URI__')->andReturn('/');
        $this->order_adapter->shouldReceive(['get' => $order]);
        $this->payment_repository->shouldReceive(['getBy' => ['resource_id' => 'pay_azerty12345', 'method' => 'standard']]);
        $this->validate_adapter->shouldReceive(['validate' => true]);
        $this->mockUnifiedServices(null, 0);

        $result = $this->action->renderDetail(42);

        $this->assertArrayHasKey('payment', $result);
        $this->assertFalse($result['refund']);
    }

    /**
     * PRE-3627 review (M3): the UHF lookup runs for every order of the module, Retail ones
     * included - a UHF failure (e.g. a table missing after a failed upgrade) must not break their
     * panel.
     */
    public function testRetailPanelStillShowsWhenTheUhfLookupThrows()
    {
        $adminClass = \Mockery::mock('AdminClass');
        $adminClass->shouldReceive(['getAdminAjaxUrl' => '/']);
        $this->dependencies->adminClass = $adminClass;
        $orderClass = \Mockery::mock('OrderClass');
        $orderClass->shouldReceive(['getUndefinedOrderHistory' => []]);
        $this->dependencies->orderClass = $orderClass;
        $order = OrderMock::get();
        $order->module = 'payplug';
        $this->payment_method->shouldReceive(['getResourceDetail' => ['mode' => 'live']]);
        $this->configuration_class->shouldReceive('getValue')->with('order_state_pending')->andReturn('42');
        $this->constant->shouldReceive('get')->with('__PS_BASE_URI__')->andReturn('/');
        $this->order_adapter->shouldReceive(['get' => $order]);
        $this->payment_repository->shouldReceive(['getBy' => ['resource_id' => 'pay_azerty12345', 'method' => 'standard']]);
        $this->validate_adapter->shouldReceive(['validate' => true]);
        $this->module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andThrow(new \Exception("Table 'ps_payplug_unified_operation' doesn't exist"));

        $result = $this->action->renderDetail(42);

        $this->assertArrayHasKey('payment', $result);
        // Checked afterwards: BaseOrderAction's catch-all addLog stub would answer an expectation.
        $this->logger->shouldHaveReceived('addLog')->with(\Mockery::pattern('/UHF detail of order \d+ could not be rendered/'), 'error')->once();
    }

    /**
     * PaymentAction inserts the payplug_payment row as soon as a Retail resource is created, before
     * any payment: an abandoned Retail attempt followed by a UHF payment on the same cart must
     * still show the UHF refund form, not the unpaid Retail resource.
     */
    public function testPaidUhfOperationWinsOverAnAbandonedRetailResourceOfTheSameCart()
    {
        // Declared before mockUhfOrder()'s own getBy => [], so this Retail row is what's returned.
        $this->payment_repository->shouldReceive(['getBy' => ['resource_id' => 'pay_abandoned', 'method' => 'standard']]);
        $this->mockUhfOrder();
        $this->mockUnifiedServices(new OperationData('op_pay', '0000', PaymentOutcome::PAID, 2900, '42'), 0);
        $this->order_class->shouldReceive('getTotalRefunded')->with(42)->andReturn(0);
        $this->payment_method->shouldReceive('getResourceDetail')->never();

        $result = $this->action->renderDetail(42);

        $this->assertSame('uhf', $result['refund']['payment_type']);
        $this->assertSame('op_pay', $result['refund']['id']);
        $this->assertArrayNotHasKey('payment', $result);
    }

    /**
     * @param string $iso_code
     * @param string $pending_state order_state_pending, the order mock being in state 42
     */
    private function mockUhfOrder($iso_code = 'USD', $pending_state = '1')
    {
        $adminClass = \Mockery::mock('AdminClass');
        $adminClass->shouldReceive(['getAdminAjaxUrl' => '/ajax']);
        $this->dependencies->adminClass = $adminClass;
        $this->constant->shouldReceive('get')->with('__PS_BASE_URI__')->andReturn('/');

        $order = OrderMock::get();
        $order->module = 'payplug';
        $order->id_currency = 2;
        $this->order_adapter->shouldReceive(['get' => $order]);
        $this->payment_repository->shouldReceive(['getBy' => []]);
        $this->validate_adapter->shouldReceive(['validate' => true]);

        $currency = new \stdClass();
        $currency->iso_code = $iso_code;
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $currency_adapter->shouldReceive('get')->with(2)->andReturn($currency);
        $this->plugin->shouldReceive(['getCurrency' => $currency_adapter]);

        $this->configuration_class->shouldReceive('getValue')->with('order_state_refund')->andReturn('7');
        $this->configuration_class->shouldReceive('getValue')->with('order_state_pending')->andReturn($pending_state);

        return $order;
    }

    /**
     * @param OperationData|null $paid_operation
     * @param int $refunded_cents
     */
    private function mockUnifiedServices($paid_operation, $refunded_cents)
    {
        $factory = \Mockery::mock('Factory');
        $operation_repository = \Mockery::mock('UnifiedOperationRepository');
        $refund_repository = \Mockery::mock('UnifiedRefundRepository');
        $amount_helper = new AmountHelper(\Mockery::mock('Dependencies'));
        $price_adapter = \Mockery::mock('PriceAdapter');

        $this->module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($factory);
        $this->module->shouldReceive('getService')->with('payplug.models.repositories.unified_refund')->andReturn($refund_repository);
        $this->module->shouldReceive('getService')->with('payplug.utilities.helper.amount')->andReturn($amount_helper);
        $this->module->shouldReceive('getService')->with('payplug.application.adapter.price')->andReturn($price_adapter);
        $factory->shouldReceive('createPaymentRepository')->andReturn($operation_repository);
        $operation_repository->shouldReceive('getPaidByOrderId')->with('42')->andReturn($paid_operation);
        $refund_repository->shouldReceive('getRefundedAmount')->with('42')->andReturn($refunded_cents);
        $price_adapter->shouldReceive('formatPrice')->andReturnUsing(function ($price, $iso_code) {
            return $iso_code . ' ' . number_format($price, 2);
        });
    }

    private function setPrivateProperty($name, $value)
    {
        $property = new \ReflectionProperty(\PayPlug\src\actions\OrderAction::class, $name);
        $property->setAccessible(true);
        $property->setValue($this->action, $value);
    }
}
