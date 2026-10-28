<?php

namespace PayPlug\Tests\utilities\helpers\AmountHelper;

use PayPlug\src\utilities\helpers\AmountHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group amount_helper
 */
class getAmountForCurrencyTest extends TestCase
{
    protected $helper;

    public function setUp(): void
    {
        $dependencies = \Mockery::mock('Dependencies');
        $this->helper = \Mockery::mock(AmountHelper::class, [$dependencies])->makePartial();
    }

    public function limitProvider()
    {
        yield ['EUR:500', '', 500];

        yield ['EUR:500', 'EUR', 500];

        yield ['EUR:500;GBP:400', 'GBP', 400];

        yield ['EUR:500;GBP:400', '', 500];

        yield ['EUR:500;GBP:400', 'gbp', 400];

        yield ['EUR:500', 'USD', null];

        yield ['', '', null];

        yield ['EUR:', 'EUR', null];

        yield ['EUR:abc', 'EUR', null];

        yield [null, 'EUR', null];

        yield [['EUR' => 500], 'EUR', null];
    }

    /**
     * @dataProvider limitProvider
     *
     * @param mixed $limit
     * @param string $currency
     * @param int|null $expected
     */
    public function testAmountIsReadForTheRequestedCurrency($limit, $currency, $expected)
    {
        $this->assertSame($expected, $this->helper->getAmountForCurrency($limit, $currency));
    }
}
