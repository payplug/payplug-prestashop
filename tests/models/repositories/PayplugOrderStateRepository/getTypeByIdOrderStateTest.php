<?php

namespace PayPlug\tests\models\repositories\PayplugOrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group payplug_order_state_repository
 *
 * @runTestsInSeparateProcesses
 */
class getTypeByIdOrderStateTest extends BasePayplugOrderStateRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order_state
     */
    public function testWhenGivenIdOrderStateIsInvalidIntegerFormat($id_order_state)
    {
        $this->assertSame(
            [],
            $this->repository->getTypeByIdOrderState($id_order_state)
        );
    }

    public function testWhenFailedRetrievingInDatabase()
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
            [],
            $this->repository->getTypeByIdOrderState($id_order_state)
        );
    }

    public function testWhenSucceedRetrievingInDatabase()
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
            $this->repository->getTypeByIdOrderState($id_order_state)
        );
    }
}
