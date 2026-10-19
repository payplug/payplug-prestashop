<?php

namespace PayPlug\Tests\utilities\helpers\AmountHelper;

use PayPlug\src\utilities\helpers\AmountHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group amount_helper
 */
class formatOneyAmountTest extends TestCase
{
    protected $amountHelper;

    public function setUp(): void
    {
        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog');
        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive('getLogger')->andReturn($logger);
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
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
            $this->amountHelper->formatOneyAmount($amount, 'EUR')
        );
    }

    /**
     * @description  test formatOneyAmount with invalid $amount provider
     *
     * @dataProvider validFormatOneyAmountDataProvider
     *
     * @param $amount_given
     * @param $message
     */
    public function testFormatOneyAmountWithValidDataProvider($amount_given, $message)
    {
        $amount = 200;
        $this->assertSame(
            [
                'result' => (float) $amount,
                'message' => $message,
            ],
            $this->amountHelper->formatOneyAmount($amount_given, 'EUR')
        );
    }

    public function testZeroDecimalCurrencyIsFormattedAsIs()
    {
        $this->assertSame(
            [
                'result' => 20000.0,
                'message' => '$amount is formatted',
            ],
            $this->amountHelper->formatOneyAmount(20000, 'JPY')
        );
    }

    /**
     * @dataProvider invalidCurrencyProvider
     *
     * @param mixed $currency
     */
    public function testFormatOneyAmountWithInvalidCurrency($currency)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => '$currency must be a non empty string',
            ],
            $this->amountHelper->formatOneyAmount(20000, $currency)
        );
    }

    public function invalidCurrencyProvider()
    {
        yield 'missing' => [''];

        yield 'null' => [null];

        yield 'not a string' => [['EUR']];
    }
}
