<?php

namespace PayPlug\tests\models\repositories\CardRepository;

use PayPlug\src\models\repositories\CardRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseCardRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(CardRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($value) {
                return $value;
            });
    }
}
