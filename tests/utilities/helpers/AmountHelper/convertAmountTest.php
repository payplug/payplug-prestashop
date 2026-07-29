<?php

namespace PayPlug\Tests\utilities\helpers\AmountHelper;

use PayPlug\src\utilities\helpers\AmountHelper;
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
}
