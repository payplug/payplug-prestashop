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
class isCurrencyTest extends TestCase
{
    protected $validator;

    protected function setUp()
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

    public function invalidArrayFormatDataProvider()
    {
        yield [42];
        yield [[]];
        yield [false];
        yield ['lorem ipsum'];
    }

    public function invalidIsoCodeFormatDataProvider()
    {
        yield ['A']; // shorter
        yield ['AA']; // shorter
        yield ['AAAA']; // longer
        yield ['?A!']; // wrong characters - !? tested
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $currency
     */
    public function testWhenGivenCurrencyIsInvalidStringFormat($currency)
    {
        $currencies = [
            'EUR',
        ];
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $currency must be a non empty string',
        ], $this->validator->isCurrency($currency, $currencies));
    }

    /**
     * @dataProvider invalidIsoCodeFormatDataProvider
     *
     * @param mixed $currency
     */
    public function testWhenGivenCurrencyIsInvalidIsoCodeFormat($currency)
    {
        $currencies = [
            'EUR',
        ];
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid iso code format given, $currency given is not valid',
        ], $this->validator->isCurrency($currency, $currencies));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $currencies
     */
    public function testWhenGivenCurrenciesIsInvalidArrayFormat($currencies)
    {
        $currency = 'EUR';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $currencies must be a non empty array',
        ], $this->validator->isCurrency($currency, $currencies));
    }

    /**
     * @dataProvider invalidIsoCodeFormatDataProvider
     *
     * @param mixed $currencies
     */
    public function testWhenGivenCurrenciesContainInvalidIsoCodeFormat($currencies)
    {
        $currency = 'EUR';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid iso code format given in array, $currency given is not valid',
        ], $this->validator->isCurrency($currency, [$currencies]));
    }

    public function testWhenGivenCurrencyIsNotInGivenCurrenciesArray()
    {
        $currency = 'USD';
        $currencies = [
            'EUR',
        ];
        $this->assertSame([
            'result' => false,
            'message' => 'Given currency does not match with the list',
        ], $this->validator->isCurrency($currency, $currencies));
    }

    public function testWhenGivenCurrencyIsInGivenCurrenciesArray()
    {
        $currency = 'EUR';
        $currencies = [
            'EUR',
        ];
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isCurrency($currency, $currencies));
    }
}
