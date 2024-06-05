<?php

namespace PayPlug\tests\models\repositories\PayplugOrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group payplug_order_state_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class setOrderStateTest extends BasePayplugOrderStateRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order_state
     */
    public function testWhenGivenIdOrderStateIsInvalidIntegerFormat($id_order_state)
    {
        $this->assertSame(
            false,
            $this->repository->setOrderState($id_order_state)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $type
     */
    public function testWhenGivenTypeIsInvalidIntegerFormat($type)
    {
        $this->assertSame(
            false,
            $this->repository->setOrderState($type)
        );
    }

    public function testWhenFailedInsertingInDatabase()
    {
        $id_order_state = 42;
        $type = 'paid';

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            false,
            $this->repository->setOrderState($id_order_state, $type)
        );
    }

    public function testWhenSucceedInsertingInDatabase()
    {
        $id_order_state = 42;
        $type = 'paid';

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertSame(
            true,
            $this->repository->setOrderState($id_order_state, $type)
        );
    }
}
