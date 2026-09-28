<?php

namespace PayPlug\tests\models\repositories\UpcLockRepository;

use PayPlug\src\models\repositories\UpcLockRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseUpcLockRepository extends BaseRepository
{
    public function setUp(): void
    {
        parent::setUp();
        $this->repository = \Mockery::mock(UpcLockRepository::class, [$this->dependencies])
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
                'table' => 'payplug_upc_lock',
                'primary' => 'id_payplug_upc_lock',
                'fields' => [
                    'lock_key' => ['type' => 'string', 'required' => true],
                    'expires_at' => ['type' => 'integer', 'required' => true],
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
