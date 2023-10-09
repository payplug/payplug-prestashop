<?php

namespace PayPlug\tests\models\repositories\PayplugOrderStateRepository;

use PayPlug\src\models\repositories\PayplugOrderStateRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BasePayplugOrderStateRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(PayplugOrderStateRepository::class, ['prefix', $this->dependencies])->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($arg) {
                return (string) $arg;
            });
    }
}
