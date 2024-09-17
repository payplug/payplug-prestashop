<?php

namespace PayPlug\tests\models\repositories\CacheRepository;

use PayPlug\src\models\repositories\CacheRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseCacheRepository extends BaseRepository
{
    public function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(CacheRepository::class, [$this->dependencies])
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
                'table' => 'payplug_cache',
                'primary' => 'id_payplug_cache',
                'fields' => [
                    'cache_key' => ['type' => 'string', 'required' => true],
                    'cache_value' => ['type' => 'string', 'required' => true],
                    'date_add' => ['type' => 'string'],
                    'date_upd' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
