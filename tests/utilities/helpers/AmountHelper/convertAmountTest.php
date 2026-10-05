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
    private $logger;

    public function setUp(): void
    {
        $this->configurationClass = \Mockery::mock('ConfigurationClass');
        $this->constantAdapter = \Mockery::mock('ConstantAdapter');
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->plugin = \Mockery::mock('Plugin');
        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive('addLog');

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
            'getLogger' => $this->logger,
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
            $this->amountHelper->convertAmount($amount, true, 'EUR')
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
            $this->amountHelper->convertAmount($amount, false, 'EUR')
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
            $this->amountHelper->convertAmount($amount, false, 'EUR')
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
            $this->amountHelper->convertAmount($amount, false, 'EUR')
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
            $this->amountHelper->convertAmount($amount, false, 'EUR')
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
            $this->amountHelper->convertAmount($amount, false, 'EUR')
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
            $this->amountHelper->convertAmount($amount, false, 'EUR')
        );
    }

    public function testZeroDecimalCurrencyIsConvertedFromCentsAsIs()
    {
        $this->assertSame(1000.0, $this->amountHelper->convertAmount(1000, true, 'JPY'));
    }

    public function testZeroDecimalCurrencyIsConvertedToCentsAsIs()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_UP);

        $this->assertSame(1000, $this->amountHelper->convertAmount(1000.4, false, 'JPY'));
    }

    public function testZeroDecimalCurrencyIsRoundedUpOnItsOwnUnit()
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

    public function testCurrencyIsCaseInsensitive()
    {
        $this->assertSame(42.42, $this->amountHelper->convertAmount(4242, true, 'eur'));
    }

    /**
     * @dataProvider invalidCurrencyProvider
     *
     * @param mixed $currency
     */
    public function testInvalidCurrencyFromCentsReturnsZeroAndLogs($currency)
    {
        $this->assertSame(0, $this->amountHelper->convertAmount(4242, true, $currency));

        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::pattern('/^AmountHelper::convertAmount\(\) - /'), 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider invalidCurrencyProvider
     *
     * @param mixed $currency
     */
    public function testInvalidCurrencyToCentsReturnsZeroAndLogs($currency)
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_HALF_UP);

        $this->assertSame(0, $this->amountHelper->convertAmount(42.42, false, $currency));

        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::pattern('/^AmountHelper::convertAmount\(\) - /'), 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testInvalidCurrencyWithRoundUpReturnsZeroWithoutRounding()
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn(self::PS_ROUND_UP);
        $this->toolsAdapter->shouldReceive('tool')->never();

        $this->assertSame(0, $this->amountHelper->convertAmount(42.42, false, ''));
    }

    /**
     * @dataProvider missingCurrencyProvider
     *
     * @param array $arguments
     */
    public function testMissingCurrencyReturnsZeroAndLogsThatTheCurrencyIsRequired($arguments)
    {
        $this->assertSame(0, call_user_func_array([$this->amountHelper, 'convertAmount'], $arguments));

        $this->logger->shouldHaveReceived('addLog')
            ->with('AmountHelper::convertAmount() - $currency is required, the amount is not converted', 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function missingCurrencyProvider()
    {
        return [
            'not given' => [[4242, true]],
            'empty' => [[4242, true, '']],
            'null' => [[4242, true, null]],
        ];
    }

    public function invalidCurrencyProvider()
    {
        return [
            'missing' => [''],
            'null' => [null],
            'not a string' => [['EUR']],
            'too long' => ['EURO'],
            'three-decimal currency' => ['KWD'],
        ];
    }

    /**
     * @dataProvider exactHalfProvider
     *
     * @param int $round_mode
     * @param float $amount
     * @param string $currency
     * @param int $expected
     */
    public function testExactHalfIsRoundedWithTheShopRoundMode($round_mode, $amount, $currency, $expected)
    {
        $this->configurationClass->shouldReceive('getValue')
            ->with('PS_PRICE_ROUND_MODE')
            ->andReturn($round_mode);

        $this->assertSame($expected, $this->amountHelper->convertAmount($amount, false, $currency));
    }

    public function exactHalfProvider()
    {
        return [
            'EUR half up' => [self::PS_ROUND_HALF_UP, 0.125, 'EUR', 13],
            'EUR half down' => [self::PS_ROUND_HALF_DOWN, 0.125, 'EUR', 12],
            'EUR half even' => [self::PS_ROUND_HALF_EVEN, 0.125, 'EUR', 12],
            'EUR half odd' => [self::PS_ROUND_HALF_ODD, 0.125, 'EUR', 13],
            'JPY half even' => [self::PS_ROUND_HALF_EVEN, 1000.5, 'JPY', 1000],
        ];
    }
}
