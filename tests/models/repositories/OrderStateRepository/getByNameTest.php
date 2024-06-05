<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_state_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getByNameTest extends BaseOrderStateRepository
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $module_name
     */
    public function testWhenGivenNameIsInvalidArrayFormat($module_name)
    {
        $test_mode = true;

        $this->assertSame(
            [],
            $this->repository->getByName($module_name, $test_mode)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $test_mode
     */
    public function testWhenGivenTestModeIsInvalidBoolFormat($test_mode)
    {
        $module_name = [];

        $this->assertSame(
            [],
            $this->repository->getByName($module_name, $test_mode)
        );
    }

    public function testWhenFailedRetrievingInDatabase()
    {
        $name = [
            'en' => 'en',
            'fr' => 'fr',
            'es' => 'es',
            'it' => 'it',
        ];
        $test_mode = false;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'leftJoin' => $this->repository,
            'where' => $this->repository,
            'whereOr' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            [],
            $this->repository->getByName($name, $test_mode)
        );
    }

    public function testWhenSucceedRetrievingInDatabase()
    {
        $name = [
            'en' => 'en',
            'fr' => 'fr',
            'es' => 'es',
            'it' => 'it',
        ];
        $test_mode = false;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'leftJoin' => $this->repository,
            'where' => $this->repository,
            'whereOr' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getByName($name, $test_mode)
        );
    }
}
