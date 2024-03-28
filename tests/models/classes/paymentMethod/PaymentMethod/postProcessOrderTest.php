<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\OrderMock;
use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class postProcessOrderTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsntValidObject($resource)
    {
        $order = OrderMock::get();
        $this->assertFalse($this->classe->postProcessOrder($resource, $order));
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $order
     */
    public function testWhenGivenOrderIsntValidObject($order)
    {
        $resource = PaymentMock::getStandard();
        $this->assertFalse($this->classe->postProcessOrder($resource, $order));
    }

    public function testWhenResourceCannotBePatched()
    {
        $order = OrderMock::get();
        $resource = PaymentMock::getStandard();

        $patch_payment = \Mockery::mock('PatchPayment');
        $patch_payment
            ->shouldReceive([
                'patchPayment' => [
                    'result' => false,
                ],
            ]);

        $this->plugin
            ->shouldReceive([
                'getApiService' => $patch_payment,
            ]);

        $this->assertFalse($this->classe->postProcessOrder($resource, $order));
    }

    public function testWhenOrderPaymentCannotBeCreate()
    {
        $order = OrderMock::get();
        $resource = PaymentMock::getStandard();

        $patch_payment = \Mockery::mock('PatchPayment');
        $patch_payment
            ->shouldReceive([
                'patchPayment' => [
                    'result' => true,
                    'resource' => $resource,
                ],
            ]);

        $order_payment_repository = \Mockery::mock('OrderPaymentRepository');
        $order_payment_repository
            ->shouldReceive([
                'createOrderPayment' => false,
            ]);
        $this->plugin
            ->shouldReceive([
                'getOrderPaymentRepository' => $order_payment_repository,
                'getApiService' => $patch_payment,
            ]);

        $this->assertFalse($this->classe->postProcessOrder($resource, $order));
    }

    public function testWhenOrderIsPostProcessed()
    {
        $order = OrderMock::get();
        $resource = PaymentMock::getStandard();

        $patch_payment = \Mockery::mock('PatchPayment');
        $patch_payment
            ->shouldReceive([
                'patchPayment' => [
                    'result' => true,
                    'resource' => $resource,
                ],
            ]);

        $order_payment_repository = \Mockery::mock('OrderPaymentRepository');
        $order_payment_repository
            ->shouldReceive([
                'createOrderPayment' => true,
            ]);
        $this->plugin
            ->shouldReceive([
                'getOrderPaymentRepository' => $order_payment_repository,
                'getApiService' => $patch_payment,
            ]);

        $this->assertTrue($this->classe->postProcessOrder($resource, $order));
    }
}
