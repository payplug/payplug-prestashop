<?php

namespace PayPlug\tests\models\repositories\PayplugOrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group payplug_order_state_repository
 *
 * @runTestsInSeparateProcesses
 */
class getAllTest extends BasePayplugOrderStateRepository
{
    public function testWhenFailedRetrievingInDatabase()
    {
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            [],
            $this->repository->getAll()
        );
    }

    public function testWhenSucceedRetrievingInDatabase()
    {
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertSame(
            true,
            $this->repository->getAll()
        );
    }
}
