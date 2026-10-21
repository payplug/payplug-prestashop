<?php

namespace PayPlug\tests\application\adapter\ProductAdapter;

use PayPlug\src\application\adapter\ProductAdapter;
use PHPUnit\Framework\TestCase;

/**
 * The core classes are mocked with alias / overload mocks, which declare them for the whole process:
 * each test must be run in its own process.
 *
 * @group unit
 * @group adapter
 * @group product_adapter
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class isOrderableRegardingStockTest extends TestCase
{
    protected $adapter;
    protected $combination;
    protected $pack;
    protected $product;
    protected $stock_available;

    public function setUp(): void
    {
        $this->combination = \Mockery::mock('overload:\Combination');
        $this->pack = \Mockery::mock('alias:\Pack');
        $this->product = \Mockery::mock('overload:\Product');
        $this->stock_available = \Mockery::mock('alias:\StockAvailable');
        $this->adapter = new ProductAdapter();
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @dataProvider packStockProvider
     *
     * @param bool $is_in_stock
     */
    public function testWhenProductIsAPack($is_in_stock)
    {
        $this->mockProductMinimalQuantity(3);
        $this->pack->shouldReceive('isPack')
            ->with(42)
            ->andReturn(true);
        // The minimal quantity of the product must be given to the pack stock check
        $this->pack->shouldReceive('isInStock')
            ->once()
            ->with(42, 3)
            ->andReturn($is_in_stock);
        $this->stock_available->shouldNotReceive('getQuantityAvailableByProduct');

        $this->assertSame($is_in_stock, $this->adapter->isOrderableRegardingStock(42));
    }

    public function packStockProvider()
    {
        yield 'pack in stock' => [true];

        yield 'pack not in stock' => [false];
    }

    public function testWhenProductIsAvailableWhenOutOfStock()
    {
        $this->mockProductMinimalQuantity(1);
        $this->mockNotAPack();
        $this->stock_available->shouldReceive('outOfStock')
            ->with(42)
            ->andReturn(1);
        $this->product->shouldReceive('isAvailableWhenOutOfStock')
            ->with(1)
            ->andReturn(true);
        $this->stock_available->shouldNotReceive('getQuantityAvailableByProduct');

        $this->assertTrue($this->adapter->isOrderableRegardingStock(42));
    }

    /**
     * @dataProvider combinationQuantityProvider
     *
     * @param int $available_quantity
     * @param bool $expected
     */
    public function testWhenCombinationQuantityIsComparedToItsMinimalQuantity($available_quantity, $expected)
    {
        $this->combination->shouldReceive('__construct')
            ->with(7)
            ->andSet('minimal_quantity', '3');
        $this->product->shouldNotReceive('__construct');
        $this->mockNotAPack();
        $this->mockNotAvailableWhenOutOfStock();
        $this->stock_available->shouldReceive('getQuantityAvailableByProduct')
            ->with(42, 7)
            ->andReturn($available_quantity);

        $this->assertSame($expected, $this->adapter->isOrderableRegardingStock(42, 7));
    }

    public function combinationQuantityProvider()
    {
        yield 'quantity greater than the minimal quantity' => [5, true];

        yield 'quantity equal to the minimal quantity' => [3, true];

        yield 'quantity lower than the minimal quantity' => [2, false];
    }

    public function testWhenProductQuantityIsLowerThanItsMinimalQuantity()
    {
        $this->mockProductMinimalQuantity(3);
        $this->mockNotAPack();
        $this->mockNotAvailableWhenOutOfStock();
        $this->stock_available->shouldReceive('getQuantityAvailableByProduct')
            ->with(42, 0)
            ->andReturn(2);

        $this->assertFalse($this->adapter->isOrderableRegardingStock(42));
    }

    /**
     * @dataProvider emptyMinimalQuantityProvider
     *
     * @param int $available_quantity
     * @param bool $expected
     */
    public function testWhenMinimalQuantityIsEmptyOneIsRequired($available_quantity, $expected)
    {
        $this->mockProductMinimalQuantity(0);
        $this->mockNotAPack();
        $this->mockNotAvailableWhenOutOfStock();
        $this->stock_available->shouldReceive('getQuantityAvailableByProduct')
            ->with(42, 0)
            ->andReturn($available_quantity);

        $this->assertSame($expected, $this->adapter->isOrderableRegardingStock(42));
    }

    public function emptyMinimalQuantityProvider()
    {
        yield 'one product in stock' => [1, true];

        yield 'no product in stock' => [0, false];
    }

    /**
     * @description Mock the product 42 (without combination) with the given minimal quantity
     *
     * @param int $minimal_quantity
     */
    private function mockProductMinimalQuantity($minimal_quantity)
    {
        $this->product->shouldReceive('__construct')
            ->with(42)
            ->andSet('minimal_quantity', (string) $minimal_quantity);
        $this->combination->shouldNotReceive('__construct');
    }

    /**
     * @description Mock the product 42 as a standard product (not a pack)
     */
    private function mockNotAPack()
    {
        $this->pack->shouldReceive('isPack')
            ->with(42)
            ->andReturn(false);
        $this->pack->shouldNotReceive('isInStock');
    }

    /**
     * @description Mock the product 42 as not orderable when out of stock
     */
    private function mockNotAvailableWhenOutOfStock()
    {
        $this->stock_available->shouldReceive('outOfStock')
            ->with(42)
            ->andReturn(0);
        $this->product->shouldReceive('isAvailableWhenOutOfStock')
            ->with(0)
            ->andReturn(false);
    }
}
