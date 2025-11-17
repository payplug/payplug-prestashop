<?php

namespace PayPlug\tests\models\repositories\ModuleRepository;

/**
 * @group unit
 * @group repository
 * @group module_repository
 */
class getActiveModuleTest extends BaseModuleRepository
{
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
