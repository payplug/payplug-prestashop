<?php

namespace PayPlug\tests\models\repositories\ModuleRepository;

use PayPlug\src\models\repositories\ModuleRepository;
use PayPlug\tests\models\repositories\BaseRepository;

/**
 * @group unit
 * @group repository
 * @group module_repository
 *
 * @runTestsInSeparateProcesses
 */
class getActiveModuleTest extends BaseRepository
{
    protected function setUp()
    {
        $this->repository = \Mockery::mock(ModuleRepository::class)->makePartial();
    }

    public function testWhenNoActiveModuleFound()
    {
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getActiveModule()
        );
    }

    public function testWhenActiveModuleAreFound()
    {
        $shop = [
            'domain' => 'website.domain.com',
            'default' => '1',
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [$shop],
        ]);

        $this->assertSame(
            [$shop],
            $this->repository->getActiveModule()
        );
    }
}
