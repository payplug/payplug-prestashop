<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getDeliveryOptionsTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoCarriersAvailable()
    {
        $this->class->shouldReceive([
            'getCarriersList' => [],
        ]);
        $this->assertSame([], $this->class->getDeliveryOptions());
    }

    public function testWhenNoValidateCarriersAvailable()
    {
        $this->class->shouldReceive([
            'getCarriersList' => [
                42,
            ],
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);

        $this->configuration_adapter->shouldReceive('get')
            ->with('PS_CARRIER_DEFAULT')
            ->andReturn('5');

        $this->assertSame([], $this->class->getDeliveryOptions());
    }

    public function testWhenCarrierIsReturned()
    {
        $this->class->shouldReceive([
            'getCarriersList' => [
                42,
            ],
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->configuration_adapter->shouldReceive('get')
            ->with('PS_CARRIER_DEFAULT')
            ->andReturn('5');

        $cart = \Mockery::mock('Cart');
        $cart->shouldReceive([
            'getPackageShippingCost' => 42,
        ]);
        $cart->id_address_delivery = 1;
        $this->context->cart = $cart;

        $carriers = [
            [
                'identifier' => '1',
                'label' => 'Carrier name',
                'detail' => 'fast or not',
                'amount' => '42',
            ],
        ];
        $this->assertSame(
            $carriers,
            $this->class->getDeliveryOptions()
        );
    }
}
