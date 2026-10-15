<?php

namespace PayPlug\tests\application\adapter\CartAdapter;

use PayPlug\src\application\adapter\CartAdapter;
use PHPUnit\Framework\TestCase;

/**
 * The core classes are mocked with alias / overload mocks, which declare them for the whole process:
 * each test must be run in its own process.
 *
 * @group unit
 * @group adapter
 * @group cart_adapter
 *
 * @runTestsInSeparateProcesses
 *
 * @preserveGlobalState disabled
 */
class checkQuantitiesTest extends TestCase
{
    protected $adapter;
    protected $cart;
    protected $validate;

    public function setUp(): void
    {
        // The adapter constructor instantiates a cart
        $this->cart = \Mockery::mock('overload:\Cart');
        $this->validate = \Mockery::mock('alias:\Validate');
        $this->adapter = new CartAdapter();
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testWhenCartIsNotLoaded()
    {
        $cart = new \Cart();
        $this->validate->shouldReceive('isLoadedObject')
            ->once()
            ->with($cart)
            ->andReturn(false);
        $this->cart->shouldNotReceive('checkQuantities');

        $this->assertFalse($this->adapter->checkQuantities($cart));
    }

    /**
     * @dataProvider checkQuantitiesProvider
     *
     * @param bool $check_quantities
     */
    public function testWhenCartIsLoaded($check_quantities)
    {
        $this->cart->shouldReceive('checkQuantities')
            ->once()
            ->andReturn($check_quantities);
        $cart = new \Cart();
        $this->validate->shouldReceive('isLoadedObject')
            ->once()
            ->with($cart)
            ->andReturn(true);

        $this->assertSame($check_quantities, $this->adapter->checkQuantities($cart));
    }

    public function checkQuantitiesProvider()
    {
        yield 'cart products can be ordered' => [true];

        yield 'one of the cart products can not be ordered' => [false];
    }
}
