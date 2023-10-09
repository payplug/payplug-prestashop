<?php

namespace PayPlug\tests\models\repositories\LockRepository;

/**
 * @group unit
 * @group repository
 * @group lock_repository
 *
 * @runTestsInSeparateProcesses
 */
class createLockTest extends BaseLockRepository
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $parameters
     */
    public function testWhenGivenParametersIsInvalidArrayFormat($parameters)
    {
        $this->assertFalse($this->repository->createLock($parameters));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'values' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->createLock($parameters));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $parameters = [
            'lorem' => 'ipsum',
        ];
        $this
            ->repository
            ->shouldReceive([
                'insert' => $this->repository,
                'into' => $this->repository,
                'fields' => $this->repository,
                'values' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->createLock($parameters));
    }
}
