<?php

namespace PayPlug\Tests\utilities\helpers\AmountHelper;

use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group amount_helper
 *
 * @dontrunTestsInSeparateProcesses
 */
class validateAmountTest extends TestCase
{
    use FormatDataProvider;

    protected $helper;

    protected function setUp()
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
            $this->helper->validateAmount($price_limit, $amount)
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
            $this->helper->validateAmount($price_limit, $amount)
        );
    }

    public function testWhenGivenAmountIsLowerThanExpected()
    {
        $amount = 0.98;
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper
            ->shouldReceive('convertAmount')
            ->andReturn(98);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $amount is lower than expected',
            ],
            $this->helper->validateAmount($price_limit, $amount)
        );
    }

    public function testWhenGivenAmountIsHigherThanExpected()
    {
        $amount = 20001.00;
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper
            ->shouldReceive('convertAmount')
            ->andReturn(2000100);
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $amount is higher than expected',
            ],
            $this->helper->validateAmount($price_limit, $amount)
        );
    }

    public function testWhenGivenAmountIsValid()
    {
        $amount = 100.00;
        $price_limit = [
            'min' => 'EUR:99',
            'max' => 'EUR:2000000',
        ];
        $this->helper
            ->shouldReceive('convertAmount')
            ->andReturn(10000);
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->helper->validateAmount($price_limit, $amount)
        );
    }
}
