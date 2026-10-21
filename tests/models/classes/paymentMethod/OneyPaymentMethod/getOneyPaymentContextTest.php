<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getOneyPaymentContextTest extends BaseOneyPaymentMethod
{
    public function testProductPricesAreConvertedInTheCartCurrency()
    {
        $this->context->currency->iso_code = 'JPY';
        $cart = new \stdClass();
        $this->cart_adapter->shouldReceive('get')->andReturn($cart);
        $this->cart_adapter->shouldReceive('getProducts')
            ->with($cart)
            ->andReturn([[
                'id_product' => 1,
                'name' => 'Mug',
                'price_wt' => 1200.0,
                'cart_quantity' => 2,
                'manufacturer_name' => 'Brand',
            ]]);
        $this->validate_adapter->shouldReceive('validate')->andReturn(true);
        $this->class->shouldReceive('getOneyDeliveryContext')->andReturn([]);
        $this->class->set('tools', $this->tools_adapter);
        $this->tools_adapter->shouldReceive('substr')
            ->andReturnUsing(function ($string) {
                return $string;
            });

        $this->class->getOneyPaymentContext();

        $this->amountHelper->shouldHaveReceived('convertAmount')->with(1200.0, false, 'JPY')->once();
        $this->addToAssertionCount(1);
    }
}
