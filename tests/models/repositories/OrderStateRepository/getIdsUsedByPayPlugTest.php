<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_repository
 */
class getIdsUsedByPayPlugTest extends BaseOrderStateRepository
{
    public function testWhenFailedRetrievingInDatabase()
    {
        $this->plugin = \Mockery::mock('Plugin');

        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $tools = \Mockery::mock('Tools');
        $tools->shouldReceive([
            'tool' => '',
        ]);
        $this->plugin->shouldReceive([
            'getTools' => $tools,
        ]);

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            [],
            $this->repository->getIdsUsedByPayPlug()
        );
    }

    public function testWhenSucceedRetrievingInDatabase()
    {
        $this->plugin = \Mockery::mock('Plugin');

        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $tools = \Mockery::mock('Tools');
        $tools->shouldReceive([
            'tool' => '',
        ]);
        $this->plugin->shouldReceive([
            'getTools' => $tools,
        ]);

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getIdsUsedByPayPlug()
        );
    }
}
