<?php

namespace PayPlug\tests\models\repositories\OrderRepository;

/**
 * @group unit
 * @group repository
 * @group order_repository
 *
 * @runTestsInSeparateProcesses
 */
class getByIdCartTest extends BaseOrderRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $cart_id
     */
    public function testWhenGivenCartIdIsInvalidIntegerFormat($cart_id)
    {
        $this->assertSame(
            [],
            $this->repository->getByIdCart($cart_id)
        );
    }

    public function testWhenNoOrderFoundForGivenCartId()
    {
        $cart_id = 42;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getByIdCart($cart_id)
        );
    }

    public function testWhenOrderAreFoundForGivenCartId()
    {
        $cart_id = 42;
        $orders = [
            [
                'id_order' => 1,
                'id_lang' => 1,
                'id_customer' => 3,
                'id_cart' => 42,
                'id_currency' => 1,
                'id_address_delivery' => 14,
                'id_address_invoice' => 14,
                'current_state' => 2,
            ],
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $orders,
        ]);

        $this->assertSame(
            $orders,
            $this->repository->getByIdCart($cart_id)
        );
    }
}
