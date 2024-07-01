<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

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
        $id_order = 42;
        $this->assertFalse($this->classe->postProcessOrder($resource, $id_order));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order
     */
    public function testWhenGivenOrderIsntValidInterger($id_order)
    {
        $resource = PaymentMock::getStandard();
        $this->assertFalse($this->classe->postProcessOrder($resource, $id_order));
    }

    public function testWhenResourceCannotBePatched()
    {
        $id_order = 42;
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

        $this->assertFalse($this->classe->postProcessOrder($resource, $id_order));
    }

    public function testWhenOrderPaymentCannotBeCreate()
    {
        $id_order = 42;
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

        $this->assertFalse($this->classe->postProcessOrder($resource, $id_order));
    }

    public function testWhenOrderIsPostProcessed()
    {
        $id_order = 42;
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

        $this->assertTrue($this->classe->postProcessOrder($resource, $id_order));
    }
}
