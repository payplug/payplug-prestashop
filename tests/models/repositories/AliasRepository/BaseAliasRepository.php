<?php

namespace PayPlug\tests\models\repositories\AliasRepository;

use PayPlug\src\models\repositories\AliasRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseAliasRepository extends BaseRepository
{
    public function setUp(): void
    {
        parent::setUp();
        $this->repository = \Mockery::mock(AliasRepository::class, [$this->dependencies])
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
                'table' => 'payplug_alias',
                'primary' => 'id_payplug_alias',
                'fields' => [
                    'id_customer' => ['type' => 'integer', 'required' => true],
                    'alias_id' => ['type' => 'string', 'required' => true],
                    'currency' => ['type' => 'string', 'required' => true],
                    'identifier' => ['type' => 'string', 'required' => true],
                    'brand' => ['type' => 'string'],
                    'last4' => ['type' => 'string'],
                    'exp_month' => ['type' => 'string'],
                    'exp_year' => ['type' => 'string'],
                    'date_add' => ['type' => 'string'],
                ],
            ],
        ]);
    }
}
