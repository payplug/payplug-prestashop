<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentMethodHashTest extends BasePaymentMethod
{
    public function testWhenCartInContextIsNotAValidObject()
    {
        $this->validate_adapter
            ->shouldReceive([
                'validate' => false,
            ]);
        $this->assertSame('', $this->classe->getPaymentMethodHash());
    }

    public function testWhenNoProductsFoundRelatedToTheCart()
    {
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->context->cart->shouldReceive([
            'getProducts' => [],
        ]);

        $this->assertSame('', $this->classe->getPaymentMethodHash());
    }

    public function testWhenHashIsReturned()
    {
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->context->cart->shouldReceive([
            'getProducts' => [
                [
                    'id_product' => 42,
                    'key' => 'value',
                ],
            ],
            'getOrderTotal' => 42,
        ]);

        $expected_hash = 'aa60dde2cf8ca2e27594856c95de692f538003608287bdd73e8d59488c5a45ca';
        $this->assertSame(
            $expected_hash,
            $this->classe->getPaymentMethodHash()
        );
    }
}
