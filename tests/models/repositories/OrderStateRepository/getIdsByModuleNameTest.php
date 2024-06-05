<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getIdsByModuleNameTest extends BaseOrderStateRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $module_name
     */
    public function testWhenGivenModuleNameIsInvalidArrayFormat($module_name)
    {
        $this->assertSame(
            [],
            $this->repository->getIdsByModuleName($module_name)
        );
    }

    public function testWhenFailedRetrievingInDatabase()
    {
        $module_name = 'payplug';

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            [],
            $this->repository->getIdsByModuleName($module_name)
        );
    }

    public function testWhenSucceedRetrievingInDatabase()
    {
        $module_name = 'payplug';

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getIdsByModuleName($module_name)
        );
    }
}
