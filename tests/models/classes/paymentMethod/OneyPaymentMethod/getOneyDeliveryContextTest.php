<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getOneyDeliveryContextTest extends BaseOneyPaymentMethod
{
    public $shop_name = 'PayPlugShop';

    public function setUp()
    {
        parent::setUp();
        $this->configuration->shouldReceive('getValue')
            ->with('PS_SHOP_NAME')
            ->andReturn($this->shop_name);
        $this->cart_adapter->shouldReceive([
            'get' => CartMock::get(),
        ]);

        $this->carrier_adapter->shouldReceive([
            'get' => CarrierMock::get(),
        ]);
    }

    public function testWhenCurrentCartIsVirtual()
    {
        $this->cart_adapter->shouldReceive([
            'isVirtualCart' => true,
        ]);
        $expected = [
            'delivery_label' => $this->shop_name,
            'expected_delivery_date' => date('Y-m-d'),
            'delivery_type' => 'edelivery',
        ];
        $this->assertSame(
            $expected,
            $this->class->getOneyDeliveryContext()
        );
    }

    public function testWhenCurrentCarrierCartIsValid()
    {
        $this->cart_adapter->shouldReceive([
            'isVirtualCart' => false,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $expected_delivery_date = 42;
        $delivery_type = 'delivery type';
        $this->carrier_adapter->shouldReceive([
            'getDefaultDelay' => $expected_delivery_date,
            'getDefaultDeliveryType' => $delivery_type,
        ]);
        $expected = [
            'delivery_label' => CarrierMock::get()->name,
            'expected_delivery_date' => date('Y-m-d', strtotime('+' . $expected_delivery_date . ' day')),
            'delivery_type' => $delivery_type,
        ];
        $this->assertSame(
            $expected,
            $this->class->getOneyDeliveryContext()
        );
    }

    public function testWhenDefaultContextIsReturned()
    {
        $this->cart_adapter->shouldReceive([
            'isVirtualCart' => false,
        ]);
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $expected = [
            'delivery_label' => $this->shop_name,
            'expected_delivery_date' => date('Y-m-d'),
            'delivery_type' => 'edelivery',
        ];
        $this->assertSame(
            $expected,
            $this->class->getOneyDeliveryContext()
        );
    }
}
