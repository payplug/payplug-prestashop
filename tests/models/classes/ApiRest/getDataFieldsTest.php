<?php

namespace PayPlug\tests\models\classes\ApiRest;

/**
 * @group unit
 * @group class
 * @group api_rest_class
 */
class getDataFieldsTest extends BaseApiRest
{
    private $stored;

    public function setUp(): void
    {
        parent::setUp();

        $this->stored = [
            'payment_methods' => '{"amex":false,"applepay":false,"bancontact":false,"deferred":false,"installment":false,"ideal":false,"mybank":false,"one_click":false,"oney":false,"satispay":false,"standard":true,"wero":false,"bizum":false,"scalapay":true}',
            'amounts' => '{}',
            'scalapay_checkout_widget' => '1',
            'scalapay_product_widget' => '0',
            'scalapay_cart_widget' => '1',
            'scalapay_amount_selectors' => '{"product":["#price"]}',
            'scalapay_merchant_token' => 'tok_secret_42',
        ];
        $this->configuration_class->shouldReceive('getValue')->andReturnUsing(function ($key) {
            return array_key_exists($key, $this->stored) ? $this->stored[$key] : false;
        });
        $this->merchant_class->shouldReceive('isLogged')->andReturn(true);
    }

    public function testTheScalapayWidgetSwitchesAreReturned()
    {
        $fields = $this->class->getDataFields();

        $this->assertTrue($fields['scalapay_checkout_widget']);
        $this->assertFalse($fields['scalapay_product_widget']);
        $this->assertTrue($fields['scalapay_cart_widget']);
    }

    public function testAMissingSwitchRowIsOff()
    {
        unset($this->stored['scalapay_cart_widget']);

        $this->assertFalse($this->class->getDataFields()['scalapay_cart_widget']);
    }

    public function testTheMerchantTokenIsNotReturned()
    {
        $fields = $this->class->getDataFields();

        $this->assertArrayNotHasKey('scalapay_merchant_token', $fields);
        $this->assertStringNotContainsString('tok_secret_42', json_encode($fields));
    }
}
