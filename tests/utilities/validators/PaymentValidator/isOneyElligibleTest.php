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
class isOneyElligibleTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidBooleanFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [null];
        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidBooleanFormatDataProvider
     *
     * @param mixed $product_quantity
     */
    public function testWhenGivenQuantityIsInvalidBooleanFormat($product_quantity)
    {
        $address = true;
        $amount = true;
        $this->assertSame([
            'result' => false,
            'code' => 'product_quantity',
            'message' => 'Invalid argument given, $product_quantity must be a boolean',
        ], $this->validator->isOneyElligible($product_quantity, $address, $amount));
    }

    /**
     * @dataProvider invalidBooleanFormatDataProvider
     *
     * @param mixed $address
     */
    public function testWhenGivenAddressIsInvalidBooleanFormat($address)
    {
        $product_quantity = true;
        $amount = true;
        $this->assertSame([
            'result' => false,
            'code' => 'address',
            'message' => 'Invalid argument given, $address must be a boolean',
        ], $this->validator->isOneyElligible($product_quantity, $address, $amount));
    }

    /**
     * @dataProvider invalidBooleanFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsInvalidBooleanFormat($amount)
    {
        $product_quantity = true;
        $address = true;
        $this->assertSame([
            'result' => false,
            'code' => 'amount',
            'message' => 'Invalid argument given, $amount must be a boolean',
        ], $this->validator->isOneyElligible($product_quantity, $address, $amount));
    }

    public function testWhenProductQuantityIsFalse()
    {
        $product_quantity = false;
        $address = true;
        $amount = true;
        $this->assertSame([
            'result' => false,
            'code' => 'product_quantity',
            'message' => 'Oney is not avaible. Reason: An error is return with the products quantity',
        ], $this->validator->isOneyElligible($product_quantity, $address, $amount));
    }

    public function testWhenAddressIsFalse()
    {
        $product_quantity = true;
        $address = false;
        $amount = true;
        $this->assertSame([
            'result' => false,
            'code' => 'address',
            'message' => 'Oney is not avaible. Reason: An error is return with the address',
        ], $this->validator->isOneyElligible($product_quantity, $address, $amount));
    }

    public function testWhenAmountIsFalse()
    {
        $product_quantity = true;
        $address = true;
        $amount = false;
        $this->assertSame([
            'result' => false,
            'code' => 'amount',
            'message' => 'Oney is not avaible. Reason: An error is return with the amount',
        ], $this->validator->isOneyElligible($product_quantity, $address, $amount));
    }

    public function testWhenAllParametersAreTrue()
    {
        $product_quantity = true;
        $address = true;
        $amount = true;
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isOneyElligible($product_quantity, $address, $amount));
    }
}
