<?php

namespace PayPlug\tests\models\repositories\EntityRepository;

use PayPlug\src\models\repositories\EntityRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseEntityRepository extends BaseRepository
{
    protected $entity;
    protected $entity_fields;
    protected $entity_id;
    protected $entity_key;
    protected $entity_value;

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

        $this->entity_id = 42;
        $this->entity_key = 'key_1';
        $this->entity_value = 'value_1';
        $this->entity_fields = [
            'key_1' => 'value_1',
            'key_2' => 42,
            'key_3' => true,
        ];
        $this->entity = \Mockery::mock('EntityObject');
        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'table',
                'primary' => 'primary',
                'fields' => [
                    'key_1' => ['type' => 'string', 'required' => true],
                    'key_2' => ['type' => 'integer'],
                    'key_3' => ['type' => 'boolean'],
                ],
            ],
        ]);
    }
}
