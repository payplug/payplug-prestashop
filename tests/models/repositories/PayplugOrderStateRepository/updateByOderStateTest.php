<?php

namespace PayPlug\tests\models\repositories\PayplugOrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group payplug_order_state_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class updateByOderStateTest extends BasePayplugOrderStateRepository
{
    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $payplug_order_state
     */
    public function testWhenGivenIdPayplugOrderStateIsInvalidIntegerFormat($payplug_order_state)
    {
        $this->assertSame(
            false,
            $this->repository->updateByOderState($payplug_order_state)
        );
    }

    public function testWhenFailedUpdatingInDatabase()
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
            $this->repository->updateByOderState($id_order_state, $type)
        );
    }

    public function testWhenSucceedUpdatingInDatabase()
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
            $this->repository->updateByOderState($id_order_state, $type)
        );
    }
}
