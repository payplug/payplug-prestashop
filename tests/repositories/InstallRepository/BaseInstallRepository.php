<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

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
