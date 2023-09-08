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
final class GetOneyDeliveryContextTest extends BaseOneyRepository
{
    public function setUp()
    {
        parent::setUp();
        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get())
        ;
    }

    public function testGetContextFromVirtual()
    {
        $this->cart->shouldReceive('get')
            ->andReturn(CartMock::get())
        ;
        $this->cart->shouldReceive('isVirtualCart')
            ->andReturn(true)
        ;

        $this->assertSame(
            [
                'delivery_label' => 'Payplug',
                'expected_delivery_date' => date('Y-m-d'),
                'delivery_type' => 'edelivery',
            ],
            $this->repo->getOneyDeliveryContext()
        );
    }

    public function testGetContext()
    {
        $this->cart->shouldReceive('get')
            ->andReturn(CartMock::get())
        ;
        $this->cart->shouldReceive('isVirtualCart')
            ->andReturn(false)
        ;

        $this->carrier->shouldReceive('get')
            ->andReturn(CarrierMock::get())
        ;
        $this->carrier->shouldReceive('getDefaultDelay')
            ->andReturn(0)
        ;
        $this->carrier->shouldReceive('getDefaultDeliveryType')
            ->andReturn('storepickup')
        ;

        $this->assertSame(
            [
                'delivery_label' => 'Carrier name',
                'expected_delivery_date' => date('Y-m-d'),
                'delivery_type' => 'storepickup',
            ],
            $this->repo->getOneyDeliveryContext()
        );
    }
}
