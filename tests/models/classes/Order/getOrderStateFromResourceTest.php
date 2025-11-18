<?php

namespace PayPlug\tests\models\classes\Order;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group order_class
 */
class getOrderStateFromResourceTest extends BaseOrder
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsInvalidStringFormat($resource)
    {
        $this->assertSame(
            [
                'result' => false,
                'status' => 'error',
            ],
            $this->class->getOrderStateFromResource($resource)
        );
    }

    public function testWhenGivenResourceIsCancelled()
    {
        $resource = PaymentMock::getOney();
        $this->payment_validator->shouldReceive([
            'isFailed' => [
                'result' => true,
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'status' => 'cancelled',
            ],
            $this->class->getOrderStateFromResource($resource)
        );
    }

    public function testWhenGivenResourceIsExpired()
    {
        $resource = PaymentMock::getStandard();
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => true,
            ],
            'isExpired' => [
                'result' => true,
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'status' => 'expired',
            ],
            $this->class->getOrderStateFromResource($resource)
        );
    }

    public function testWhenGivenResourceIsFailed()
    {
        $resource = PaymentMock::getStandard();
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => false,
            ],
            'isExpired' => [
                'result' => false,
            ],
            'isFailed' => [
                'result' => true,
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'status' => 'error',
            ],
            $this->class->getOrderStateFromResource($resource)
        );
    }

    public function testWhenRelatedProductIsOutOfStock()
    {
        $resource = PaymentMock::getStandard(['metadata' => ['Order' => 42]]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => false,
            ],
            'isExpired' => [
                'result' => false,
            ],
            'isFailed' => [
                'result' => false,
            ],
        ]);

        $order = \Mockery::mock('Order');
        $order->shouldReceive([
            'getOrderDetailList' => [
                [
                    'name' => 'product',
                    'product_quantity_in_stock' => 0,
                ],
            ],
        ]);
        $order_adapter = \Mockery::mock('OrderAdapter');
        $order_adapter->shouldReceive([
            'get' => $order,
        ]);
        $configuration = \Mockery::mock('Configuration');
        $configuration->shouldReceive('get')
            ->with('PS_STOCK_MANAGEMENT')
            ->andReturn(true);
        $this->plugin->shouldReceive([
            'getConfiguration' => $configuration,
            'getOrder' => $order_adapter,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'status' => 'outofstock_paid',
            ],
            $this->class->getOrderStateFromResource($resource)
        );
    }

    public function testWhenGivenResourceIsPaid()
    {
        $resource = PaymentMock::getStandard(['metadata' => ['Order' => 42]]);
        $this->payment_validator->shouldReceive([
            'isDeferred' => [
                'result' => false,
            ],
            'isExpired' => [
                'result' => false,
            ],
            'isFailed' => [
                'result' => false,
            ],
        ]);

        $order = \Mockery::mock('Order');
        $order->shouldReceive([
            'getOrderDetailList' => [
                [
                    'name' => 'product',
                    'product_quantity_in_stock' => 0,
                ],
            ],
        ]);
        $order_adapter = \Mockery::mock('OrderAdapter');
        $order_adapter->shouldReceive([
            'get' => $order,
        ]);
        $configuration = \Mockery::mock('Configuration');
        $configuration->shouldReceive('get')
            ->with('PS_STOCK_MANAGEMENT')
            ->andReturn(false);
        $this->plugin->shouldReceive([
            'getConfiguration' => $configuration,
            'getOrder' => $order_adapter,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'status' => 'paid',
            ],
            $this->class->getOrderStateFromResource($resource)
        );
    }
}
