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

namespace PayPlug\tests\repositories\OrderStateRepository;

use PayPlug\tests\mock\OrderStateMock;

/**
 * @group unit
 * @group repository
 * @group order_state
 * @group order_state_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class CreateTest extends BaseOrderStateRepository
{
    private $configKey;
    private $orderStateMock;
    private $state;

    public function setUp()
    {
        parent::setUp();

        $this->configKey = 'PAYPLUG_ORDER_STATE';
        $this->orderStateMock = OrderStateMock::get();
        $this->state = [
            'cfg' => 'CONFIG_KEY',
            'template' => 'order_state_template',
            'name' => 'order_state',
            'type' => 'nothing',
        ];

        $this->repo
            ->shouldReceive([
                'getConfigKey' => $this->configKey,
                'setType' => true,
            ])
        ;

        $this->config
            ->shouldReceive('get')
            ->with($this->configKey)
            ->andReturn(false)
        ;

        $this->config
            ->shouldReceive('updateValue')
            ->andReturnUsing(function ($key, $value) {
                return $key . '-' . $value;
            })
        ;
    }

    public function invalidDataProvider()
    {
        // test invalid name
        yield ['', ['key' => 'value']];
        yield [false, ['key' => 'value']];
        yield [null, ['key' => 'value']];
        yield [42, ['key' => 'value']];
        yield [['key' => 'value'], ['key' => 'value']];

        // test invalid state
        yield ['name', []];
        yield ['name', 'wrong_params'];
        yield ['name', 42];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $name
     * @param mixed $state
     */
    public function testWithInvalidDataProvider($name, $state)
    {
        $this->assertSame(
            false,
            $this->repo->create($name, $state)
        );
    }

    public function testWithIdOrderStateFoundByConfig()
    {
        $this->repo
            ->shouldReceive([
                'getOrderStateByConfiguration' => $this->orderStateMock->id,
            ])
        ;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $this->orderStateMock,
            ])
        ;

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithIdOrderStateFoundByTemplate()
    {
        $this->repo
            ->shouldReceive([
                'getOrderStateByConfiguration' => false,
                'getOrderStateByTemplate' => $this->orderStateMock->id,
            ])
        ;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $this->orderStateMock,
            ])
        ;

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithIdOrderStateFoundByName()
    {
        $this->repo
            ->shouldReceive([
                'getOrderStateByConfiguration' => false,
                'getOrderStateByTemplate' => false,
                'findByName' => $this->orderStateMock->id,
            ])
        ;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $this->orderStateMock,
            ])
        ;

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithOrderStateCreated()
    {
        $this->repo
            ->shouldReceive([
                'getOrderStateByConfiguration' => false,
                'getOrderStateByTemplate' => false,
                'findByName' => false,
                'add' => $this->orderStateMock->id,
            ])
        ;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $this->orderStateMock,
            ])
        ;

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithOrderStateForced()
    {
        $new_order_state = 4242;

        $this->repo
            ->shouldReceive([
                'getOrderStateByConfiguration' => false,
                'getOrderStateByTemplate' => false,
                'findByName' => $this->orderStateMock->id,
                'add' => $new_order_state,
            ])
        ;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $this->orderStateMock,
            ])
        ;

        $response = $this->repo->create($this->state['name'], $this->state, false, true);

        $this->assertSame(
            $this->configKey . '-' . $new_order_state,
            $response
        );
    }

    public function testWithOrderStateDeleted()
    {
        $new_order_state = 4242;

        $this->repo
            ->shouldReceive([
                'getOrderStateByConfiguration' => false,
                'getOrderStateByTemplate' => false,
                'findByName' => $this->orderStateMock->id,
                'add' => $new_order_state,
            ])
        ;

        $this->orderStateMock->deleted = 1;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $this->orderStateMock,
            ])
        ;

        $this->assertSame(
            $this->configKey . '-' . $new_order_state,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithInvalidOrderStateObject()
    {
        $new_order_state = 4242;

        $this->repo
            ->shouldReceive([
                'getOrderStateByConfiguration' => false,
                'getOrderStateByTemplate' => false,
                'findByName' => $this->orderStateMock->id,
                'add' => $new_order_state,
            ])
        ;

        $this->orderStateMock->deleted = 1;

        $this->order_state_adapter
            ->shouldReceive([
                'get' => $this->orderStateMock,
            ])
        ;

        $this->assertSame(
            $this->configKey . '-' . $new_order_state,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }
}
