<?php

namespace PayPlug\Tests\utilities\helpers\AmountHelper;

use PayPlug\src\utilities\helpers\AmountHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group amount_helper
 *
 * @dontrunTestsInSeparateProcesses
 */
class formatOneyAmountTest extends TestCase
{
    protected $amountHelper;

    protected function setUp()
    {
        $dependencies = \Mockery::mock('Dependencies');
        $this->amountHelper = new AmountHelper($dependencies);
    }

    /**
     * @description invalid formatOneyAmount params provider
     *
     * @return \Generator
     */
    public function invalidFormatOneyAmountDataProvider()
    {
        yield [null, '$amount must be a int type'];
        yield ['test', '$amount must be a int type'];
        yield [['key' => 'value'], '$amount must be a int type'];
        yield [false, '$amount must be a int type'];
    }

    public function validFormatOneyAmountDataProvider()
    {
        yield [20000, '$amount is formatted'];
    }

    /**
     * @description  test formatOneyAmount with invalid $amount provider
     *
     * @dataProvider invalidFormatOneyAmountDataProvider
     *
     * @param $amount
     * @param $errorMsg
     */
    public function testFormatOneyAmountWithInvalidDataProvider($amount, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->amountHelper->formatOneyAmount($amount)
        );
    }

    /**
     * @description  test formatOneyAmount with invalid $amount provider
     *
     * @dataProvider validFormatOneyAmountDataProvider
     *
     * @param $amount
     * @param $message
     */
    public function testFormatOneyAmountWithValidDataProvider($amount, $message)
    {
        $this->assertSame(
            [
                'result' => 200,
                'message' => $message,
            ],
            $this->amountHelper->formatOneyAmount($amount)
        );
    }
}
