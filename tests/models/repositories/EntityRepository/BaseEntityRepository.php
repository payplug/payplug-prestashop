<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

use PayPlug\src\models\repositories\EntityRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseEntityRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(EntityRepository::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
        $this->repository
            ->shouldReceive('getTableName')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }
}
