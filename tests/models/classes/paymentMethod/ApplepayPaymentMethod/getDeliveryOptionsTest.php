<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group applepay_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getDeliveryOptionsTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoCarriersAvailable()
    {
        $this->classe
            ->shouldReceive([
                'getCarriersList' => [],
            ]);
        $this->assertSame([], $this->classe->getDeliveryOptions());
    }

    public function testWhenNoValidateCarriersAvailable()
    {
        $this->classe
            ->shouldReceive([
                'getCarriersList' => [
                    42,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => false,
            ]);
        $this->assertSame([], $this->classe->getDeliveryOptions());
    }

    public function testWhenCarrierIsReturned()
    {
        $this->classe
            ->shouldReceive([
                'getCarriersList' => [
                    42,
                ],
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);

        $cart = \Mockery::mock('Cart');
        $cart
            ->shouldReceive([
                'getPackageShippingCost' => 42,
            ]);
        $cart->id_address_delivery = 1;
        $this->context->cart = $cart;

        $carriers = [
            [
                'identifier' => 1,
                'label' => 'Carrier name',
                'detail' => 'fast or not',
                'amount' => 42,
            ],
        ];
        $this->assertSame(
            $carriers,
            $this->classe->getDeliveryOptions()
        );
    }
}
