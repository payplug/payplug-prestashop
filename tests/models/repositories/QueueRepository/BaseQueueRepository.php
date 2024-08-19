<?php

namespace PayPlug\tests\models\repositories\QueueRepository;

use PayPlug\src\models\repositories\QueueRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseQueueRepository extends BaseRepository
{
    protected $entity;
    protected $entity_id;

    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(QueueRepository::class, [$this->dependencies])
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
