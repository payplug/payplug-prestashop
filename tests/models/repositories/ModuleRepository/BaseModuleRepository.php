<?php

namespace PayPlug\tests\models\repositories\ModuleRepository;

use PayPlug\src\models\repositories\ModuleRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseModuleRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(ModuleRepository::class, ['prefix', $this->dependencies])->makePartial();
    }
}
