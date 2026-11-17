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
class setDeliveryOptionTest extends TestCase
{
    protected $adapter;
    protected $cart;
    protected $validate;

    public function setUp(): void
    {
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
        $this->cart->shouldNotReceive('setDeliveryOption');
        $this->cart->shouldNotReceive('update');

        $this->assertFalse($this->adapter->setDeliveryOption($cart, [2 => '1,']));
    }

    /**
     * @dataProvider invalidDeliveryOptionProvider
     *
     * @param mixed $delivery_option
     */
    public function testWhenDeliveryOptionIsInvalid($delivery_option)
    {
        $cart = new \Cart();
        $this->validate->shouldReceive('isLoadedObject')
            ->andReturn(true);
        $this->cart->shouldNotReceive('setDeliveryOption');
        $this->cart->shouldNotReceive('update');

        $this->assertFalse($this->adapter->setDeliveryOption($cart, $delivery_option));
    }

    public function invalidDeliveryOptionProvider()
    {
        yield 'empty array' => [[]];

        yield 'string' => ['1,'];

        yield 'null' => [null];
    }

    /**
     * @dataProvider updateResultProvider
     *
     * @param bool $update_result
     */
    public function testWhenDeliveryOptionIsSaved($update_result)
    {
        $this->cart->shouldReceive('setDeliveryOption')
            ->once()
            ->with([2 => '1,']);
        $this->cart->shouldReceive('update')
            ->once()
            ->andReturn($update_result);
        $cart = new \Cart();
        $this->validate->shouldReceive('isLoadedObject')
            ->once()
            ->with($cart)
            ->andReturn(true);

        $this->assertSame($update_result, $this->adapter->setDeliveryOption($cart, [2 => '1,']));
    }

    public function updateResultProvider()
    {
        yield 'cart updated' => [true];

        yield 'cart not updated' => [false];
    }
}
