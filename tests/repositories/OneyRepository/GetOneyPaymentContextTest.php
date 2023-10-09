<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyPaymentContextTest extends BaseOneyRepository
{
    public function setUp()
    {
        parent::setUp();

        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get())
        ;
        $this->carrier->shouldReceive([
            'get' => CarrierMock::get(),
            'getDefaultDelay' => 0,
            'getDefaultDeliveryType' => 'storepickup',
        ]);

        $this->dependencies
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $cent = false) {
                if ($cent) {
                    return round($amount / 100, 2);
                }

                return (int) $amount * 100;
            })
        ;
    }

    public function testGetContext()
    {
        $this->cart->shouldReceive([
            'get' => CartMock::get(),
            'getProducts' => [CartMock::getProducts()[0]],
            'isVirtualCart' => false,
        ]);

        $this->assertSame(
            [
                'cart' => [
                    [
                        'merchant_item_id' => '1',
                        'name' => 'Pull imprimé colibri - Size : S',
                        'price' => 3446,
                        'quantity' => 1,
                        'total_amount' => 3446,
                        'brand' => 'Studio Design',
                        'delivery_label' => 'Carrier name',
                        'expected_delivery_date' => date('Y-m-d'),
                        'delivery_type' => 'storepickup',
                    ],
                ],
            ],
            $this->repo->getOneyPaymentContext()
        );
    }

    public function testGetContextWithNoProducts()
    {
        $this->cart->shouldReceive([
            'get' => CartMock::get(),
            'getProducts' => [],
            'isVirtualCart' => false,
        ]);

        $this->assertSame(
            [
                'cart' => [],
            ],
            $this->repo->getOneyPaymentContext()
        );
    }

    public function testGetContextWithWrongCart()
    {
        $this->cart->shouldReceive([
            'get' => null,
            'getProducts' => [],
            'isVirtualCart' => false,
        ]);
        $this->assertSame(
            [
                'cart' => [],
            ],
            $this->repo->getOneyPaymentContext()
        );
    }

    public function testGetContextWithTooLongName()
    {
        $this->cart->shouldReceive([
            'get' => CartMock::get(),
            'getProducts' => [CartMock::getProducts()[4]],
            'isVirtualCart' => false,
        ]);

        $this->assertSame(
            [
                'cart' => [
                    [
                        'merchant_item_id' => '5',
                        'name' => 'Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun
                Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours brun Coussin ours 
                brun Coussin ours brun C',
                        'price' => 2268,
                        'quantity' => 1,
                        'total_amount' => 2268,
                        'brand' => 'Studio Design Studio DesignStudio DesignStudio Design Studio Design Studio Design
                 Studio Design Studio Design Studio Design Studio Design Studio Design Studio Design Studio Design Studio 
                 Design Studio Design Studio ',
                        'delivery_label' => 'Carrier name',
                        'expected_delivery_date' => date('Y-m-d'),
                        'delivery_type' => 'storepickup',
                    ],
                ],
            ],
            $this->repo->getOneyPaymentContext()
        );
    }
}
