<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class isOneyCountryTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
    }

    public function invalidIsoCodeFormatDataProvider()
    {
        yield ['F'];
        yield ['FRA'];
        yield ['F5'];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $shipping_country_iso
     */
    public function testWithInvalidShippingIsoCodeFormat($shipping_country_iso)
    {
        $billing_country_iso = 'FR';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $shipping_country_iso must be a non empty string',
        ], $this->validator->isOneyCountry($shipping_country_iso, $billing_country_iso));
    }

    /**
     * @dataProvider invalidIsoCodeFormatDataProvider
     *
     * @param mixed $shipping_country_iso
     */
    public function testWhenShippingIsoCodeFormatIsInvalidIsoCode($shipping_country_iso)
    {
        $billing_country_iso = 'FR';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument format given, $shipping_country_iso not a valid iso code',
        ], $this->validator->isOneyCountry($shipping_country_iso, $billing_country_iso));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $billing_country_iso
     */
    public function testWithInvalidBillingIsoCodeFormat($billing_country_iso)
    {
        $shipping_country_iso = 'FR';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $billing_country_iso must be a non empty string',
        ], $this->validator->isOneyCountry($shipping_country_iso, $billing_country_iso));
    }

    /**
     * @dataProvider invalidIsoCodeFormatDataProvider
     *
     * @param mixed $billing_country_iso
     */
    public function testWhenBillingIsoCodeFormatIsInvalidIsoCode($billing_country_iso)
    {
        $shipping_country_iso = 'FR';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument format given, $billing_country_iso not a valid iso code',
        ], $this->validator->isOneyCountry($shipping_country_iso, $billing_country_iso));
    }

    public function testWhenGivenIsoCodeDoesNotMatch()
    {
        $shipping_country_iso = 'FR';
        $billing_country_iso = 'IT';
        $this->assertSame([
            'result' => false,
            'message' => 'The given iso code do not match together',
        ], $this->validator->isOneyCountry($shipping_country_iso, $billing_country_iso));
    }

    public function testWhenGivenIsoCodeMatch()
    {
        $shipping_country_iso = 'FR';
        $billing_country_iso = 'FR';
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isOneyCountry($shipping_country_iso, $billing_country_iso));
    }
}
