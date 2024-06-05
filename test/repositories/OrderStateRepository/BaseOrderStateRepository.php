<?php

namespace PayPlug\tests\repositories\OrderStateRepository;

use PayPlug\src\repositories\OrderStateRepository;
use PayPlug\tests\repositories\RepositoryBase;

class BaseOrderStateRepository extends RepositoryBase
{
    public function setUp()
    {
        parent::setUp();

        $this->constant->shouldReceive('get')
            ->with('_DB_PREFIX_')
            ->andReturn('')
        ;

        $this->constant->shouldReceive('get')
            ->with('_PS_MODULE_DIR_')
            ->andReturn('')
        ;

        $this->repo = \Mockery::mock(OrderStateRepository::class, [
            $this->configuration,
            $this->constant,
            $this->dependencies,
            $this->language,
            $this->order_state_adapter,
            $this->query,
            $this->tools,
            $this->validate,
            $this->myLogPhp,
        ])->makePartial();
    }
}
