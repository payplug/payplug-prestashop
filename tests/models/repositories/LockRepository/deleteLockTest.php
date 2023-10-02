<?php

namespace PayPlug\tests\models\repositories\LockRepository;

/**
 * @group unit
 * @group repository
 * @group lock_repository
 *
 * @runTestsInSeparateProcesses
 */
class deleteLockTest extends BaseLockRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testWhenGivenIdCountryIsInvalidIntegerFormat($id_cart)
    {
        $this->assertFalse($this->repository->deleteLock($id_cart));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $id_cart = 42;
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->deleteLock($id_cart));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $id_cart = 42;
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->deleteLock($id_cart));
    }
}
