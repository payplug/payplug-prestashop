<?php

namespace PayPlug\tests\models\repositories\CardRepository;

use PayPlug\src\models\repositories\CardRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseCardRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(CardRepository::class, [$this->dependencies])
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

        $this->entity->shouldReceive([
            'getDefinition' => [
                'table' => 'payplug_card',
                'primary' => 'id_payplug_card',
                'fields' => [
                    'id_customer' => ['type' => 'int', 'required' => true],
                    'id_company' => ['type' => 'int', 'required' => true],
                    'is_sandbox' => ['type' => 'int', 'required' => true],
                    'id_card' => ['type' => 'string', 'required' => true],
                    'last4' => ['type' => 'string', 'required' => true],
                    'exp_month' => ['type' => 'string', 'required' => true],
                    'exp_year' => ['type' => 'string', 'required' => true],
                    'brand' => ['type' => 'string', 'required' => false],
                    'country' => ['type' => 'string', 'required' => true],
                    'metadata' => ['type' => 'string', 'required' => false],
                ],
            ],
        ]);
    }
}
