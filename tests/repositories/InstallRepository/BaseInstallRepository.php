<?php

namespace PayPlug\tests\repositories\InstallRepository;

use PayPlug\src\models\entities\OrderStateEntity;
use PayPlug\src\repositories\InstallRepository;
use PayPlug\tests\repositories\RepositoryBase;

class BaseInstallRepository extends RepositoryBase
{
    protected $order_state_entity;

    public function setUp()
    {
        parent::setUp();

        $this->order_state_entity = $this->order_state_entity ? $this->order_state_entity : new OrderStateEntity();

        $this->constant->shouldReceive('get')
            ->with('_PS_MODULE_DIR_')
            ->andReturn('')
        ;

        $this->repo = \Mockery::mock(InstallRepository::class, [
            $this->config,
            $this->constant,
            $this->context,
            $this->dependencies,
            $this->order_state,
            $this->order_state_entity,
            $this->order_state_adapter,
            $this->query,
            $this->shop,
            $this->sql,
            $this->tools,
            $this->validate,
            $this->myLogPhp,
        ])
            ->shouldAllowMockingProtectedMethods()
            ->makePartial()
        ;

        $this->shop
            ->shouldReceive('isFeatureActive')
            ->andReturn(false)
        ;
    }
}
