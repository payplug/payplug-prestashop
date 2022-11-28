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
class isAmountTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidAmountFormatDataProvider()
    {
        yield ['lorem Ipsum'];
        yield [true];
        yield [0];
        yield [['key' => 'value']];
    }

    public function invalidLimitsFormatDataProvider()
    {
        yield ['lorem Ipsum'];
        yield [true];
        yield [42];
        yield [[]];
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield ['lorem Ipsum'];
        yield [true];
        yield ['42'];
        yield [['key' => 'value']];
    }

    /**
     * @dataProvider invalidAmountFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWithInvalidAmountFormat($amount)
    {
        $limits = [
            'min' => 0,
            'max' => 2000000,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument given, $amount must be a non null integer',
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    /**
     * @dataProvider invalidLimitsFormatDataProvider
     *
     * @param mixed $limits
     */
    public function testWithInvalidLimitsFormat($limits)
    {
        $amount = 4242;
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument $limits, $amount must be a non empty array',
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    public function testWhenKeyLimitsMinIsMissing()
    {
        $amount = 4242;
        $limits = [
            'max' => 2000000,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Missing array key: $limits[min]',
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $min
     */
    public function testWhenKeyLimitsMinIsInvalidFormat($min)
    {
        $amount = 4242;
        $limits = [
            'min' => $min,
            'max' => 2000000,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong array value, $limits[min] must be an integer',
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    public function testWhenKeyLimitsMaxIsMissing()
    {
        $amount = 4242;
        $limits = [
            'min' => 0,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Missing array key: $limits[max]',
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $max
     */
    public function testWhenKeyLimitsMaxIsInvalidFormat($max)
    {
        $amount = 4242;
        $limits = [
            'min' => 0,
            'max' => $max,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong array value, $limits[max] must be an integer',
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    public function testWhenAmountIsLowerThanTheMinimumLimit()
    {
        $amount = 1000;
        $limits = [
            'min' => 5000,
            'max' => 10000,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong amount given: ' . $amount
                    . ', $amount must be between ' . $limits['min']
                    . ' and ' . $limits['max'],
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    public function testWhenAmountIsUpperThanTheMinimumLimit()
    {
        $amount = 20000;
        $limits = [
            'min' => 5000,
            'max' => 10000,
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong amount given: ' . $amount
                    . ', $amount must be between ' . $limits['min']
                    . ' and ' . $limits['max'],
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }

    public function testWhenAmountBetweenTheLimit()
    {
        $amount = 1000;
        $limits = [
            'min' => 100,
            'max' => 10000,
        ];
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->validator->isAmount($amount, $limits)
        );
    }
}
