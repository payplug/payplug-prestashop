<?php

namespace PayPlug\tests\models\repositories\LockRepository;

use PayPlug\src\models\repositories\LockRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseLockRepository extends BaseRepository
{
    public function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(LockRepository::class, [$this->dependencies])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->repository->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
        $this->repository->shouldReceive('getTableName')
            ->andReturnUsing(function ($value) {
                return $value;
            });

        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'payplug_lock',
                'primary' => 'id_payplug_lock',
                'fields' => [
                    'id_cart' => ['type' => 'integer', 'required' => true],
                    'id_order' => ['type' => 'string', 'required' => true],
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
