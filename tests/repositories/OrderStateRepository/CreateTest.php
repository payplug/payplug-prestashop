<?php

namespace PayPlug\tests\repositories\OrderStateRepository;

use PayPlug\tests\mock\OrderStateMock;

/**
 * @group unit
 * @group old_repository
 * @group order_state
 * @group order_state_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CreateTest extends BaseOrderStateRepository
{
    private $configKey;
    private $orderStateMock;
    private $state;

    public function setUp()
    {
        parent::setUp();

        $this->configKey = 'order_state_test';
        $this->orderStateMock = OrderStateMock::get();
        $this->state = [
            'cfg' => 'TEST',
            'template' => 'order_state_template',
            'name' => 'test',
            'type' => 'nothing',
        ];

        $this->repo->shouldReceive([
            'setType' => true,
        ])
        ;

        $this->configuration->shouldReceive('getValue')
            ->with($this->configKey)
            ->andReturn(false)
        ;

        $this->configuration->shouldReceive('set')
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
        $this->config->shouldReceive('get')
            ->with($this->state['cfg'])
            ->andReturn($this->orderStateMock->id);

        $this->order_state_adapter->shouldReceive([
            'get' => $this->orderStateMock,
        ]);

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithIdOrderStateFoundByTemplate()
    {
        $this->config->shouldReceive('get')
            ->with($this->state['cfg'])
            ->andReturn(false);

        $this->repo->shouldReceive([
            'getOrderStateByTemplate' => $this->orderStateMock->id,
        ]);

        $this->order_state_repository->shouldReceive([
            'getOrderStateByTemplate' => 42,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $this->orderStateMock,
        ]);

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithIdOrderStateFoundByName()
    {
        $this->config->shouldReceive('get')
            ->with($this->state['cfg'])
            ->andReturn(false);

        $this->repo->shouldReceive([
            'getOrderStateByTemplate' => false,
            'getByName' => $this->orderStateMock->id,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $this->orderStateMock,
        ]);

        $this->order_state_repository->shouldReceive([
            'getOrderStateByTemplate' => 42,
        ]);

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithOrderStateCreated()
    {
        $this->config->shouldReceive('get')
            ->with($this->state['cfg'])
            ->andReturn(false);

        $this->repo->shouldReceive([
            'getOrderStateByTemplate' => false,
            'getByName' => false,
            'add' => $this->orderStateMock->id,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $this->orderStateMock,
        ]);

        $this->order_state_repository->shouldReceive([
            'getOrderStateByTemplate' => 42,
        ]);

        $this->assertSame(
            $this->configKey . '-' . $this->orderStateMock->id,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithOrderStateForced()
    {
        $new_order_state = 4242;

        $this->config->shouldReceive('get')
            ->with($this->state['cfg'])
            ->andReturn(false);

        $this->repo->shouldReceive([
            'getOrderStateByTemplate' => false,
            'getByName' => $this->orderStateMock->id,
            'add' => $new_order_state,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $this->orderStateMock,
        ]);

        $this->order_state_repository->shouldReceive([
            'getOrderStateByTemplate' => 42,
        ]);

        $this->assertSame(
            $this->configKey . '-' . $new_order_state,
            $this->repo->create($this->state['name'], $this->state, false, true)
        );
    }

    public function testWithOrderStateDeleted()
    {
        $new_order_state = 4242;
        $this->orderStateMock->deleted = 1;

        $this->config->shouldReceive('get')
            ->with($this->state['cfg'])
            ->andReturn(false);

        $this->repo->shouldReceive([
            'getOrderStateByTemplate' => false,
            'getByName' => $this->orderStateMock->id,
            'add' => $new_order_state,
        ]);

        $this->order_state_repository->shouldReceive([
            'getOrderStateByTemplate' => 42,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $this->orderStateMock,
        ]);

        $this->assertSame(
            $this->configKey . '-' . $new_order_state,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }

    public function testWithInvalidOrderStateObject()
    {
        $new_order_state = 4242;
        $this->orderStateMock->deleted = 1;

        $this->config->shouldReceive('get')
            ->with($this->state['cfg'])
            ->andReturn(false);

        $this->repo->shouldReceive([
            'getByName' => $this->orderStateMock->id,
            'add' => $new_order_state,
        ]);

        $this->order_state_repository->shouldReceive([
            'getOrderStateByTemplate' => 42,
        ]);

        $this->order_state_adapter->shouldReceive([
            'get' => $this->orderStateMock,
        ]);

        $this->assertSame(
            $this->configKey . '-' . $new_order_state,
            $this->repo->create($this->state['name'], $this->state, false)
        );
    }
}
