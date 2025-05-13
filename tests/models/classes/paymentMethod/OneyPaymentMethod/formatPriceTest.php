<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class formatPriceTest extends BaseOneyPaymentMethod
{
    public $price;
    public $currency;

    public function setUp()
    {
        parent::setUp();
        $this->price = 4242;
        $this->currency = CurrencyMock::get();
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $price
     */
    public function testWhenGivenPriceIsInvalidIntegerFormat($price)
    {
        $this->assertSame(
            '',
            $this->class->formatPrice($price, $this->currency)
        );
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $currency
     */
    public function testWhenGivenCurrencyIsInvalidObjectFormat($currency)
    {
        $this->assertSame(
            '',
            $this->class->formatPrice($this->price, $currency)
        );
    }
}
