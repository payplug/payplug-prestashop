<?php

namespace PayPlug\Tests\utilities\helpers\AmountHelper;

use PayPlug\src\utilities\helpers\AmountHelper;
use PayplugUnifiedCore\Exceptions\InvalidCurrencyException;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group amount_helper
 */
class convertAmountTest extends TestCase
{
    private const PS_ROUND_HALF_UP = PHP_ROUND_HALF_UP;
    private const PS_ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;
    private const PS_ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;
    private const PS_ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;
    private const PS_ROUND_UP = 5;
    private const PS_ROUND_DOWN = 6;
    private $dependencies;
    private $configurationClass;
    private $constantAdapter;
    private $plugin;
    private $toolsAdapter;

    public function setUp(): void
    {
        $this->configurationClass = \Mockery::mock('ConfigurationClass');
        $this->constantAdapter = \Mockery::mock('ConstantAdapter');
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->plugin = \Mockery::mock('Plugin');
        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');

        $this->constantAdapter
            ->shouldReceive('get')
            ->andReturnUsing(function ($arg) {
                switch ($arg) {
                    case 'PS_ROUND_UP':
                        return self::PS_ROUND_UP;

                    case 'PS_ROUND_DOWN':
                        return self::PS_ROUND_DOWN;

                    case 'PS_ROUND_HALF_DOWN':
                        return self::PS_ROUND_HALF_DOWN;

                    case 'PS_ROUND_HALF_EVEN':
                        return self::PS_ROUND_HALF_EVEN;

                    case 'PS_ROUND_HALF_ODD':
                        return self::PS_ROUND_HALF_ODD;

                    case 'PS_ROUND_HALF_UP':
                    default:
                        return self::PS_ROUND_HALF_UP;
                }
            });

        $this->plugin->shouldReceive([
            'getConstant' => $this->constantAdapter,
            'getConfigurationClass' => $this->configurationClass,
            'getTools' => $this->toolsAdapter,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->amountHelper = new AmountHelper($this->dependencies);
    }

    public function testWhenGivenAmountIsFromCents()
    {
        $amount = 4242;
        $amount_expected = 42.42;
        $this->assertSame(
            (float) $amount_expected,
            $this->amountHelper->convertAmount($amount, true)
        );
    }

    public function testWhenGivenAmountIsToCentsWithRoundUp()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_UP);

        $this->toolsAdapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($arg, $amount) {
                return ceil($amount);
            });

        $amount = 14.425555555;
        $amount_expected = 1443;
        $this->assertSame(
            $amount_expected,
            $this->amountHelper->convertAmount($amount)
        );
    }

    public function testWhenGivenAmountIsToCentsWithRoundDown()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_DOWN);

        $this->toolsAdapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($arg, $amount) {
                return floor($amount);
            });

        $amount = 14.425555555;
        $amount_expected = 1442;
        $this->assertSame(
            $amount_expected,
            $this->amountHelper->convertAmount($amount)
        );
    }

    public function testWhenGivenAmountIsToCentsWithHalfUp()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_UP);
        $amount = 14.425555555;
        $amount_expected = 1443;
        $this->assertSame(
            $amount_expected,
            $this->amountHelper->convertAmount($amount)
        );
    }

    public function testWhenGivenAmountIsToCentsWithHalfDown()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_DOWN);
        $amount = 14.425555555;
        $amount_expected = 1443;
        $this->assertSame(
            $amount_expected,
            $this->amountHelper->convertAmount($amount)
        );
    }

    public function testWhenGivenAmountIsToCentsWithHalfEven()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_EVEN);
        $amount = 14.425555555;
        $amount_expected = 1443;
        $this->assertSame(
            $amount_expected,
            $this->amountHelper->convertAmount($amount)
        );
    }

    public function testWhenGivenAmountIsToCentsWithHalfOdd()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_ODD);
        $amount = 14.425555555;
        $amount_expected = 1443;
        $this->assertSame(
            $amount_expected,
            $this->amountHelper->convertAmount($amount)
        );
    }

    public function testWhenGivenAmountIsFromCentsInAZeroDecimalCurrency()
    {
        $this->assertSame(1000.0, $this->amountHelper->convertAmount(1000, true, 'JPY'));
    }

    public function testWhenGivenAmountIsFromCentsInAThreeDecimalCurrencyThenThrows()
    {
        $this->expectException(InvalidCurrencyException::class);

        $this->amountHelper->convertAmount(1000, true, 'KWD');
    }

    public function testWhenGivenAmountIsToCentsInATwoDecimalCurrency()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_UP);

        $this->assertSame(1790, $this->amountHelper->convertAmount(17.90, false, 'EUR'));
        $this->assertSame(1999, $this->amountHelper->convertAmount(19.99, false, 'usd'));
    }

    public function testWhenGivenAmountIsToCentsInAZeroDecimalCurrency()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_UP);

        $this->assertSame(1000, $this->amountHelper->convertAmount(1000.0, false, 'JPY'));
        $this->assertSame(1001, $this->amountHelper->convertAmount(1000.5, false, 'JPY'));
    }

    public function testWhenGivenAmountIsToCentsInAThreeDecimalCurrencyThenThrows()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_UP);

        $this->expectException(InvalidCurrencyException::class);

        $this->amountHelper->convertAmount(12.345, false, 'KWD');
    }

    public function testWhenGivenAmountIsToCentsWithRoundUpInAZeroDecimalCurrency()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_UP);
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->with('ps_round', 1000.2)
            ->andReturnUsing(function ($arg, $amount) {
                return ceil($amount);
            });

        $this->assertSame(1001, $this->amountHelper->convertAmount(1000.2, false, 'JPY'));
    }

    public function testWhenGivenAmountIsToCentsWithRoundDownInAZeroDecimalCurrency()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_DOWN);
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->with('ps_round', 1000.8)
            ->andReturnUsing(function ($arg, $amount) {
                return floor($amount);
            });

        $this->assertSame(1000, $this->amountHelper->convertAmount(1000.8, false, 'JPY'));
    }

    public function testWhenGivenAmountIsToCentsWithRoundUpInAThreeDecimalCurrencyThenThrows()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_UP);
        $this->toolsAdapter->shouldNotReceive('tool');

        $this->expectException(InvalidCurrencyException::class);

        $this->amountHelper->convertAmount(12.345, false, 'KWD');
    }
}
