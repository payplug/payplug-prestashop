<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class isEligibleByCountryTest extends TestCase
{
    use FormatDataProvider;

    protected $validator;
    protected $country;
    protected $payment_method;
    protected $payment_methods_countries;

    public function setUp()
    {
        $this->validator = new paymentValidator();
        $this->country = 'FR';
        $this->payment_method = 'default';
        $this->payment_methods_countries = '{"default":["FR","BE"]}';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param $country
     */
    public function testWhenGivenCountryIsInvalidStringFormat($country)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => '$country must be a string type',
            ],
            $this->validator->isEligibleByCountry($country, $this->payment_method, $this->payment_methods_countries)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param $payment_method
     */
    public function testWhenGivenPaymentMethodIsInvalidStringFormat($payment_method)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => '$payment_method must be a string type',
            ],
            $this->validator->isEligibleByCountry($this->payment_method, $payment_method, $this->payment_methods_countries)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param $payment_methods_countries
     */
    public function testWhenGivenPaymentMethodCountryIsInvalidStringFormat($payment_methods_countries)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => '$payment_methods_countries must be a string type',
            ],
            $this->validator->isEligibleByCountry($this->payment_method, $this->payment_method, $payment_methods_countries)
        );
    }

    public function testWhenGivenCountryIsEligible()
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => $this->country . ' is eligible for ' . $this->payment_method,
            ],
            $this->validator->isEligibleByCountry($this->country, $this->payment_method, $this->payment_methods_countries)
        );
    }
}
