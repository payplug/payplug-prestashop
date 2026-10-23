<?php

namespace PayPlug\Tests\utilities\helpers\AmountHelper;

use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group amount_helper
 */
class validateAmountTest extends TestCase
{
    use FormatDataProvider;

    protected $helper;

    public function setUp(): void
    {
        $dependencies = \Mockery::mock('Dependencies');
        $this->helper = \Mockery::mock(AmountHelper::class, [$dependencies])->makePartial();
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $price_limit
     */
    public function testWhenGivenPriceLimitIsInvalidArrayFormat($price_limit)
    {
        $amount = 42.42;
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong paramaters given, $price_limit must be a non empty array',
            ],
            $this->helper->validateAmount($price_limit, $amount, 'EUR')
        );
    }

    /**
     * @dataProvider invalidFloatFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsInvalidArrayFormat($amount)
    {
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong paramaters given, $amount must be a non null float',
            ],
            $this->helper->validateAmount($price_limit, $amount, 'EUR')
        );
    }

    public function testWhenGivenAmountIsLowerThanExpected()
    {
        $amount = 0.98;
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->andReturn(98);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $amount is lower than expected',
            ],
            $this->helper->validateAmount($price_limit, $amount, 'EUR')
        );
    }

    public function testWhenGivenAmountIsHigherThanExpected()
    {
        $amount = 20001.00;
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->andReturn(2000100);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $amount is higher than expected',
            ],
            $this->helper->validateAmount($price_limit, $amount, 'EUR')
        );
    }

    public function testWhenGivenAmountIsValid()
    {
        $amount = 100.00;
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->with($amount, false, 'EUR')
            ->andReturn(10000);
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->helper->validateAmount($price_limit, $amount, 'EUR')
        );
    }

    /**
     * @dataProvider invalidCurrencyProvider
     *
     * @param mixed $currency
     */
    public function testWhenGivenCurrencyIsInvalid($currency)
    {
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper->shouldReceive('convertAmount')->never();
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Wrong paramaters given, $currency must be a non empty string',
            ],
            $this->helper->validateAmount($price_limit, 100.00, $currency)
        );
    }

    public function testWhenGivenCurrencyCannotBeConverted()
    {
        $price_limit = [
            'min' => 'EUR:99;KWD:99',
            'max' => 'EUR:2000000;KWD:2000000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->with(100.00, false, 'KWD')
            ->andReturn(0);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $amount is lower than expected',
            ],
            $this->helper->validateAmount($price_limit, 100.00, 'KWD')
        );
    }

    public function invalidCurrencyProvider()
    {
        return [
            'missing' => [''],
            'null' => [null],
            'not a string' => [['EUR']],
        ];
    }

    public function testWhenCurrencyGivenUsesTheMatchingEntry()
    {
        $price_limit = [
            'min' => 'EUR:500;GBP:400',
            'max' => 'EUR:9000;GBP:8000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->andReturn(450);

        $this->assertTrue($this->helper->validateAmount($price_limit, 4.50, 'GBP')['result']);
        $this->assertFalse($this->helper->validateAmount($price_limit, 4.50, 'EUR')['result']);
    }

    public function testBoundsAreInclusive()
    {
        $price_limit = [
            'min' => 'EUR:500',
            'max' => 'EUR:9000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->andReturn(500, 9000);

        $this->assertTrue($this->helper->validateAmount($price_limit, 5.00, 'EUR')['result']);
        $this->assertTrue($this->helper->validateAmount($price_limit, 90.00, 'EUR')['result']);
    }

    public function testWhenTheLimitsHaveNoEntryForTheCurrencyTheAmountIsNotBounded()
    {
        $price_limit = [
            'min' => 'EUR:500',
            'max' => 'EUR:9000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->with(100.00, false, 'USD')
            ->andReturn(10000);

        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->helper->validateAmount($price_limit, 100.00, 'USD')
        );
    }

    /**
     * @dataProvider singleBoundProvider
     *
     * @param array $price_limit
     * @param int $cents
     * @param array $expected
     */
    public function testAMissingBoundDoesNotLimitTheAmount($price_limit, $cents, $expected)
    {
        $this->helper->shouldReceive('convertAmount')
            ->andReturn($cents);

        $this->assertSame($expected, $this->helper->validateAmount($price_limit, 42.00, 'EUR'));
    }

    public function singleBoundProvider()
    {
        $valid = ['result' => true, 'message' => ''];

        return [
            'only min, amount above' => [['min' => 'EUR:500'], 100000000, $valid],
            'only min, amount below' => [
                ['min' => 'EUR:500'],
                499,
                ['result' => false, 'message' => 'Given $amount is lower than expected'],
            ],
            'only max, amount below' => [['max' => 'EUR:9000'], 1, $valid],
            'only max, amount above' => [
                ['max' => 'EUR:9000'],
                9001,
                ['result' => false, 'message' => 'Given $amount is higher than expected'],
            ],
            'empty max' => [['min' => 'EUR:500', 'max' => ''], 100000000, $valid],
        ];
    }

    public function testWhenACurrencyWithoutLimitsCannotBeConvertedTheAmountIsRefused()
    {
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper->shouldReceive('convertAmount')
            ->with(100.00, false, 'KWD')
            ->andReturn(0);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $amount is lower than expected',
            ],
            $this->helper->validateAmount($price_limit, 100.00, 'KWD')
        );
    }
}
