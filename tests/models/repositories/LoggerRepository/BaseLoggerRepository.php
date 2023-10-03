<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

use PayPlug\src\models\repositories\LoggerRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseLoggerRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(LoggerRepository::class, ['prefix', $this->dependencies])->makePartial();
    }
}
