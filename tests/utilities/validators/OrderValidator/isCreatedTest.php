<?php

namespace PayPlug\tests\utilities\validators\OrderValidator;

use PayPlug\src\utilities\validators\orderValidator;
use PayPlug\tests\mock\OrderMock;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group order_validator
 *
 * @runTestsInSeparateProcesses
 */
class isCreatedTest extends TestCase
{
    protected $validator;
    protected $order;

    public function setUp()
    {
        $this->validator = new orderValidator();
        $this->order = OrderMock::get(); // given order id_cart = 42
    }

    public function invalidObjectFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield ['string'];
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $order
     */
    public function testWithInvalidOrderFormat($order)
    {
        $id_cart = 42;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $order must be a non null object',
        ], $this->validator->isCreated($order, $id_cart));
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [['key' => 'value']];
        yield [false];
        yield ['string'];
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testWithInvalidintegerFormat($id_cart)
    {
        $order = OrderMock::get();
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $id_cart must be a non null integer',
        ], $this->validator->isCreated($order, $id_cart));
    }

    public function testWithOrderWithoutId()
    {
        unset($this->order->id);
        $id_cart = 42;

        $this->assertSame([
            'result' => false,
            'message' => 'Invalid object given, $order should have a non null id',
        ], $this->validator->isCreated($this->order, $id_cart));
    }

    public function testWithOrderWithoutCartId()
    {
        unset($this->order->id_cart);
        $id_cart = 42;

        $this->assertSame([
            'result' => false,
            'message' => 'Invalid object given, $order should have a non null cart id',
        ], $this->validator->isCreated($this->order, $id_cart));
    }

    public function testWhenOrderCartIdAndGivenCartIdDontMatch()
    {
        $id_cart = 123;

        $this->assertSame([
            'result' => false,
            'message' => 'Given order does not match with given cart id',
        ], $this->validator->isCreated($this->order, $id_cart));
    }

    public function testWhenOrderCartIdAndGivenCartIdMatch()
    {
        $id_cart = 42;
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isCreated($this->order, $id_cart));
    }
}
