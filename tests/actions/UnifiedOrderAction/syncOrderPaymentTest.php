<?php

namespace PayPlug\tests\actions\UnifiedOrderAction;

/**
 * @group unit
 * @group action
 * @group unified_order_action
 */
class syncOrderPaymentTest extends BaseUnifiedOrderAction
{
    public function testFillsTheTransactionIdAndLabelOfAPaymentWithoutTransactionId()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $order_payment = $this->mockOrderPayment('');
        $this->mockOrderPayments($mocks, [$order_payment]);

        $this->assertTrue($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame('pay_1', $order_payment->transaction_id);
        $this->assertSame(self::PAYMENT_LABEL, $order_payment->payment_method);
        $this->assertSame(1, $order_payment->updates);
    }

    public function testNeverOverwritesAnExistingTransactionId()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $recorded = $this->mockOrderPayment('pay_recorded');
        $empty = $this->mockOrderPayment('');
        $this->mockOrderPayments($mocks, [$recorded, $empty]);

        $this->assertTrue($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame('pay_recorded', $recorded->transaction_id);
        $this->assertSame('Payplug', $recorded->payment_method);
        $this->assertSame(0, $recorded->updates);
        $this->assertSame('pay_1', $empty->transaction_id);
    }

    /**
     * A payment the merchant added from the back office ("Add payment") is not Payplug's.
     */
    public function testNeverTouchesAPaymentAddedByTheMerchant()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $manual = $this->mockOrderPayment('', 'Bank transfer');
        $payplug = $this->mockOrderPayment('');
        $this->mockOrderPayments($mocks, [$manual, $payplug]);

        $this->assertTrue($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame('', $manual->transaction_id);
        $this->assertSame('Bank transfer', $manual->payment_method);
        $this->assertSame(0, $manual->updates);
        $this->assertSame('pay_1', $payplug->transaction_id);
    }

    public function testChangesNothingWhenTheOnlyPaymentWithoutTransactionIdIsTheMerchants()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $manual = $this->mockOrderPayment('', 'Cash on delivery');
        $this->mockOrderPayments($mocks, [$manual]);

        $this->assertFalse($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame('', $manual->transaction_id);
        $this->assertSame(0, $manual->updates);
    }

    /**
     * The payment validateOrder() records for an order validated in the paid state carries the
     * order label: the payment label, or the module name for an order validated without it.
     *
     * @dataProvider validationPaymentMethodProvider
     *
     * @param string $payment_method
     */
    public function testFillsThePaymentRecordedAtValidation($payment_method)
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $order_payment = $this->mockOrderPayment('', $payment_method);
        $this->mockOrderPayments($mocks, [$order_payment]);

        $this->assertTrue($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame('pay_1', $order_payment->transaction_id);
        $this->assertSame(self::PAYMENT_LABEL, $order_payment->payment_method);
    }

    public function validationPaymentMethodProvider()
    {
        return [
            'payment label' => [self::PAYMENT_LABEL],
            'module name' => ['payplug'],
        ];
    }

    public function testChangesNothingWhenEveryPaymentHasItsTransactionId()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $recorded = $this->mockOrderPayment('pay_1');
        $order = $this->mockOrderPayments($mocks, [$recorded]);

        $this->assertFalse($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame(0, $recorded->updates);
        $this->assertSame(0, $order->updates);
    }

    public function testChangesNothingForAnOrderWithoutPayment()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $order = $this->mockOrderPayments($mocks, []);

        $this->assertFalse($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame(0, $order->updates);
    }

    /**
     * @dataProvider emptyPaymentIdProvider
     *
     * @param mixed $payment_id
     */
    public function testDoesNothingWithoutPaymentId($payment_id)
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $mocks['order_adapter']->shouldReceive('get')->with(99)->never();

        $this->assertFalse($this->mockOrderAction($dependencies)->syncOrderPayment(99, $payment_id));
    }

    public function emptyPaymentIdProvider()
    {
        return [
            'null' => [null],
            'empty' => [''],
        ];
    }

    /**
     * Orders created before the label was set at validation carry the module name.
     */
    public function testReplacesTheModuleNameUsedAsOrderLabel()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $order = $this->mockOrderPayments($mocks, [$this->mockOrderPayment('pay_1')], 'payplug');

        $this->assertTrue($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame(self::PAYMENT_LABEL, $order->payment);
        $this->assertSame(1, $order->updates);
    }

    public function testKeepsAnyOtherOrderLabel()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $order = $this->mockOrderPayments($mocks, [$this->mockOrderPayment('')], 'Label edited by the merchant');

        $this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1');

        $this->assertSame('Label edited by the merchant', $order->payment);
        $this->assertSame(0, $order->updates);
    }

    public function testNeverTouchesAnOrderOfAnotherModule()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $order_payment = $this->mockOrderPayment('');
        $order = $this->mockOrderPayments($mocks, [$order_payment], 'payplug');
        $order->module = 'ps_checkout';

        $this->assertFalse($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
        $this->assertSame('', $order_payment->transaction_id);
        $this->assertSame('payplug', $order->payment);
    }

    public function testLogsAndReportsAFailureWithoutThrowing()
    {
        [$dependencies, $mocks] = $this->mockDependenciesForOrderCreation();
        $mocks['order_adapter']->shouldReceive('get')->with(99)->andThrow(new \Exception('database unavailable'));
        $mocks['logger']->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/payment of order 99 not updated with payment id pay_1.*database unavailable/'));

        $this->assertFalse($this->mockOrderAction($dependencies)->syncOrderPayment(99, 'pay_1'));
    }
}
