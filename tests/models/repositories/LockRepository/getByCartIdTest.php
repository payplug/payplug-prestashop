<?php

namespace PayPlug\tests\models\repositories\LockRepository;

/**
 * @group unit
 * @group repository
 * @group lock_repository
 *
 * @runTestsInSeparateProcesses
 */
class getByCartIdTest extends BaselockRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $cart_id
     */
    public function testWhenGivenIdCustomerIsInvalidIntegerFormat($cart_id)
    {
        $this->assertSame(
            [],
            $this->repository->getByCartId($cart_id)
        );
    }

    public function testWhenNolockIsReturnForGivenCustomer()
    {
        $cart_id = 4242;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getByCartId($cart_id)
        );
    }

    public function testWhenlockIsReturnForGivenCustomer()
    {
        $cart_id = 4242;
        $date = date('Y-m-d H:i:s');
        $lock = [
            'id_payplug_lock' => 42,
            'id_cart' => 42,
            'id_order' => 'ipn',
            'date_add' => $date,
            'date_upd' => $date,
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $lock,
        ]);

        $this->assertSame(
            $lock,
            $this->repository->getByCartId($cart_id)
        );
    }
}
