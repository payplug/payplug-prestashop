<?php

namespace PayPlug\tests\utilities\validators\OrderValidator;

use PayPlug\src\utilities\validators\orderValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group order_validator
 *
 * @runTestsInSeparateProcesses
 */
class isSameAmountTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new orderValidator();
    }

    public function invalidFloatFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield ['string'];
    }

    /**
     * @dataProvider invalidFloatFormatDataProvider
     *
     * @param mixed $first_amount
     */
    public function testWithInvalidFirstAmountFormat($first_amount)
    {
        $second_amount = 42.42;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $first_amount must be a non null float',
        ], $this->validator->isSameAmount($first_amount, $second_amount));
    }

    /**
     * @dataProvider invalidFloatFormatDataProvider
     *
     * @param mixed $second_amount
     */
    public function testWithInvalidSecondAmountFormat($second_amount)
    {
        $first_amount = 42.42;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $second_amount must be a non null float',
        ], $this->validator->isSameAmount($first_amount, $second_amount));
    }

    public function testWhenGivenAmountsDoesNotMatch()
    {
        $first_amount = 42.42;
        $second_amount = 24.24;
        $this->assertSame([
            'result' => false,
            'message' => 'The given amounts are differents',
        ], $this->validator->isSameAmount($first_amount, $second_amount));
    }

    public function testWhenGivenAmountsMatch()
    {
        $first_amount = 42.42;
        $second_amount = 42.42;
        $this->assertSame([
            'result' => true,
            'message' => 'The given amounts are the same',
        ], $this->validator->isSameAmount($first_amount, $second_amount));
    }
}
