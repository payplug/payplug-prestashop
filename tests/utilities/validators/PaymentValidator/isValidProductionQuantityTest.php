<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 */
class isValidProductionQuantityTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [0];

        yield [['key' => 'value']];

        yield [true];

        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $quantity
     */
    public function testWhenGivenQuantityIsInvalidIntegerFormat($quantity)
    {
        $limit = 1000;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $quantity must be a non null integer',
        ], $this->validator->isValidProductQuantity($quantity, $limit));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $limit
     */
    public function testWhenGivenLimitIsInvalidIntegerFormat($limit)
    {
        $quantity = 42;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $limit must be a non null integer',
        ], $this->validator->isValidProductQuantity($quantity, $limit));
    }

    public function testWhenGivenQuantityIsBeyondTheLimit()
    {
        $quantity = 1000;
        $limit = 42;
        $this->assertSame([
            'result' => false,
            'message' => 'The given quantity given exceed the limit',
        ], $this->validator->isValidProductQuantity($quantity, $limit));
    }

    public function testWhenGivenQuantityIsValid()
    {
        $quantity = 42;
        $limit = 1000;
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isValidProductQuantity($quantity, $limit));
    }
}
