<?php

namespace PayPlug\tests\models\repositories\StateRepository;

use PayPlug\src\models\repositories\StateRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseStateRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(StateRepository::class, ['prefix', $this->dependencies])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($arg) {
                return (string) $arg;
            });
        $this->repository
            ->shouldReceive('getTableName')
            ->andReturnUsing(function ($value) {
                return $value;
            });

        $this->entity_id = 42;
        $this->entity = \Mockery::mock('EntityObject');
        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'table',
                'primary' => 'primary',
            ],
        ]);
    }
}
