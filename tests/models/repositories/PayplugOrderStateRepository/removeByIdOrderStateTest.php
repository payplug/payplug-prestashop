<?php

namespace PayPlug\tests\models\repositories\PayplugOrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group payplug_order_state_repository
 *
 * @runTestsInSeparateProcesses
 */
class removeByIdOrderStateTest extends BasePayplugOrderStateRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order_state
     */
    public function testWhenGivenIdPayplugOrderStateIsInvalidIntegerFormat($id_order_state)
    {
        $this->assertSame(
            false,
            $this->repository->removeByIdOrderState($id_order_state)
        );
    }

    public function testWhenFailedRemovingInDatabase()
    {
        $id_order_state = 42;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            false,
            $this->repository->removeByIdOrderState($id_order_state)
        );
    }

    public function testWhenSucceedRemovingInDatabase()
    {
        $id_order_state = 42;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertSame(
            true,
            $this->repository->removeByIdOrderState($id_order_state)
        );
    }
}
