<?php

namespace PayPlugTest;

use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group ci
 * @group recommended
 * @group ignore
 */
class PayplugTest extends TestCase
{
    /**
     * @test
     */
    public function CheckAssertion()
    {
        $this->assertEquals(1, true);
    }
//
//    // test Payplug::convertAmount
//    public function testConvertAmountToCents()
//    {
//        $amount = 19.99;
//        $new_amount = $this->module->convertAmount($amount);
//        $this->assertEquals('1999', $new_amount);
//    }
//
//    public function testConvertAmountToEuros()
//    {
//        $amount = 1995;
//        $new_amount = $this->module->convertAmount($amount,true);
//        $this->assertEquals('19.95', $new_amount);
//    }
//
//    // test Payplug::getOneyPriceLimit
//    public function testGetOneyPriceLimitWithIdCurrency()
//    {
//        $id_currency = PAYPLUG_TEST_EURO_ID;
//        $limits = $this->module->getOneyPriceLimit($id_currency);
//        $min = $this->module->convertAmount($limits['min'],true);
//        $max = $this->module->convertAmount($limits['max'],true);
//        $this->assertEquals(PAYPLUG_TEST_ONEY_MIN, $min);
//        $this->assertEquals(PAYPLUG_TEST_ONEY_MAX, $max);
//    }
//
//    public function testGetOneyPriceLimitWithNoCurrency()
//    {
//        $id_currency = false;
//        $limits = $this->module->getOneyPriceLimit($id_currency);
//        $min = $this->module->convertAmount($limits['min'],true);
//        $max = $this->module->convertAmount($limits['max'],true);
//        $this->assertEquals(PAYPLUG_TEST_ONEY_MIN, $min);
//        $this->assertEquals(PAYPLUG_TEST_ONEY_MAX, $max);
//    }
//
//    public function testGetOneyPriceLimitInvalideCurrency()
//    {
//        $id_currency = 'lorem';
//        $limits = $this->module->getOneyPriceLimit($id_currency);
//        $this->assertEquals(false, $limits['min']);
//        $this->assertEquals(false, $limits['max']);
//    }
//
//    // test Payplug::isValidOneyCountry
//    public function testValidateOneyCountryWithSameCountry(){
//        $shipping_iso = 'fr';
//        $billing_iso = 'fr';
//        $return = $this->module->isValidOneyCountry($shipping_iso,$billing_iso);
//        $this->assertEquals(true, $return['result']);
//    }
//
//    public function testValidateOneyCountryWithDifferentCountry(){
//        $shipping_iso = 'fr';
//        $billing_iso = 'it';
//        $return = $this->module->isValidOneyCountry($shipping_iso,$billing_iso);
//        $this->assertEquals(false, $return['result']);
//        $this->assertEquals('different', $return['type']);
//    }
//
//    public function testValidateOneyCountryWithInvalidCountry(){
//        $shipping_iso = 'de';
//        $billing_iso = 'de';
//        $return = $this->module->isValidOneyCountry($shipping_iso,$billing_iso);
//        $this->assertEquals(false, $return['result']);
//        $this->assertEquals('invalid', $return['type']);
//    }
//
//    // test Payplug::isValidOneyAmount method
//    public function testIsValidOneyAmountWithToLowAmount(){
//        $amount = 99;
//        $return = $this->module->isValidOneyAmount($amount);
//        $this->assertEquals(false, $return['result']);
//    }
//
//    public function testIsValidOneyAmountWithToHightAmount(){
//        $amount = 3001;
//        $return = $this->module->isValidOneyAmount($amount);
//        $this->assertEquals(false, $return['result']);
//    }
//
//    public function testIsValidOneyAmountWithValidAmount(){
//        $amount = 500;
//        $return = $this->module->isValidOneyAmount($amount);
//        $this->assertEquals(true, $return['result']);
//    }
//
//    // test Payplug::getOneySimulations
//    public function testGetOneySimulations3x()
//    {
//        $amount = '500';
//
//        $operation = array('x3_with_fees');
//        $return = $this->module->getOneySimulationsSandbox($amount, PAYPLUG_TEST_DEFAULT_COUNTRY, $operation);
//        if($return['result']) {
//            $this->assertArrayHasKey('installments', $return['simulations']['x3_with_fees']);
//            $this->assertArrayHasKey('total_cost', $return['simulations']['x3_with_fees']);
//            $this->assertArrayHasKey('nominal_annual_percentage_rate', $return['simulations']['x3_with_fees']);
//            $this->assertArrayHasKey('effective_annual_percentage_rate', $return['simulations']['x3_with_fees']);
//            $this->assertArrayHasKey('down_payment_amount', $return['simulations']['x3_with_fees']);
//        }
//    }
//
//    public function testGetOneySimulations4x()
//    {
//        $amount = '500';
//
//        $operation = array('x4_with_fees');
//        $return = $this->module->getOneySimulationsSandbox($amount, PAYPLUG_TEST_DEFAULT_COUNTRY, $operation);
//
//        if($return['result']) {
//            $this->assertArrayHasKey('installments', $return['simulations']['x4_with_fees']);
//            $this->assertArrayHasKey('total_cost', $return['simulations']['x4_with_fees']);
//            $this->assertArrayHasKey('nominal_annual_percentage_rate', $return['simulations']['x4_with_fees']);
//            $this->assertArrayHasKey('effective_annual_percentage_rate', $return['simulations']['x4_with_fees']);
//            $this->assertArrayHasKey('down_payment_amount', $return['simulations']['x4_with_fees']);
//        }
//    }
}
