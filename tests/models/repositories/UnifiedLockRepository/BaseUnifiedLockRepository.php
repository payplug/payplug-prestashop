<?php

namespace PayPlug\tests\models\repositories\UnifiedLockRepository;

use PayPlug\src\models\repositories\UnifiedLockRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseUnifiedLockRepository extends BaseRepository
{
    public function setUp(): void
    {
        parent::setUp();
        $this->repository = \Mockery::mock(UnifiedLockRepository::class, [$this->dependencies])
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
                'table' => 'payplug_unified_lock',
                'primary' => 'id_payplug_unified_lock',
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
