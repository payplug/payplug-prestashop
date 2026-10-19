<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\src\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class hydratePaymentTabFromPaymentDataTest extends BaseOneyPaymentMethod
{
    private $payment_tab;

    public function setUp(): void
    {
        parent::setUp();

        $this->payment_tab = $this->default_payment_tab;
        $this->payment_tab['billing']['country'] = 'FR';
        $this->payment_tab['shipping']['country'] = 'BE';
    }

    public function testBillingForeignMobileIsNotSent()
    {
        $payment_tab = $this->hydrate(['billing-mobile_phone_number' => '+32470123456']);

        $this->assertArrayHasKey('mobile_phone_number', $payment_tab['billing']);
        $this->assertNull($payment_tab['billing']['mobile_phone_number']);
    }

    public function testBillingMobileOfTheBillingCountryIsFormatted()
    {
        $payment_tab = $this->hydrate(['billing-mobile_phone_number' => '0612345678']);

        $this->assertSame('+33612345678', $payment_tab['billing']['mobile_phone_number']);
    }

    public function testShippingMobileIsCheckedAgainstTheShippingCountry()
    {
        $payment_tab = $this->hydrate(['shipping-mobile_phone_number' => '0470123456']);

        $this->assertSame('+32470123456', $payment_tab['shipping']['mobile_phone_number']);
    }

    public function testShippingMobileOfAnotherCountryIsNotSent()
    {
        $payment_tab = $this->hydrate(['shipping-mobile_phone_number' => '+33612345678']);

        $this->assertNull($payment_tab['shipping']['mobile_phone_number']);
    }

    public function testEmptyMobileIsNullNeverEmptyString()
    {
        $payment_tab = $this->hydrate(['billing-mobile_phone_number' => '']);

        $this->assertNull($payment_tab['billing']['mobile_phone_number']);
    }

    private function hydrate(array $payment_data)
    {
        $method = new \ReflectionMethod(OneyPaymentMethod::class, 'hydratePaymentTabFromPaymentData');
        $method->setAccessible(true);

        return $method->invoke($this->class, $this->payment_tab, $payment_data);
    }
}
