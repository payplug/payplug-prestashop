<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group amount_helper
 *
 * @dontrunTestsInSeparateProcesses
 */
class isEligibleByCountryTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
    }

    /**
     * @description invalid isEligibleByCountry params provider
     *
     * @return \Generator
     */
    public function invalidIsEligibleByAmountDataProvider()
    {
        yield [null, 'giropay', '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$country must be a string type'];
        yield [42, 'giropay', '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$country must be a string type'];
        yield [['key' => 'value'], 'giropay', '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$country must be a string type'];
        yield [false, 'giropay', '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$country must be a string type'];

        yield ['DE', null, '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$payment_method must be a string type'];
        yield ['DE', 42, '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$payment_method must be a string type'];
        yield ['DE', ['key' => 'value'], '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$payment_method must be a string type'];
        yield ['DE', false, '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', '$payment_method must be a string type'];

        yield ['DE', 'giropay', null, '$payment_methods_countries must be a string type'];
        yield ['DE', 'giropay', 42, '$payment_methods_countries must be a string type'];
        yield ['DE', 'giropay', ['key' => 'value'], '$payment_methods_countries must be a string type'];
        yield ['DE', 'giropay', false, '$payment_methods_countries must be a string type'];
    }

    /**
     * @description valid isEligibleByCountry params provider
     *
     * @return \Generator
     */
    public function validIsEligibleByAmountDataProvider()
    {
        yield ['DE', 'giropay', '{"default":["all"],"giropay":["DE"],"ideal":["NL"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"]}', 'DE is eligible for giropay'];
    }

    /**
     * @description  test isEligibleByAmount with invalid data provider
     *
     * @dataProvider invalidIsEligibleByAmountDataProvider
     *
     * @param $country
     * @param $payment_method
     * @param $payment_methods_countries
     * @param $errorMsg
     */
    public function testIsEligibleByAmountWithInvalidDataProvider($country, $payment_method, $payment_methods_countries, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->validator->isEligibleByCountry($country, $payment_method, $payment_methods_countries)
        );
    }

    /**
     * @description  test isEligibleByAmount with invalid data provider
     *
     * @dataProvider validIsEligibleByAmountDataProvider
     *
     * @param $country
     * @param $payment_method
     * @param $payment_methods_countries
     * @param $message
     */
    public function testIsEligibleByAmountWithValidDataProvider($country, $payment_method, $payment_methods_countries, $message)
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => $message,
            ],
            $this->validator->isEligibleByCountry($country, $payment_method, $payment_methods_countries)
        );
    }
}
