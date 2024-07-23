<?php

namespace PayPlug\tests\actions\OrderStateAction;

use PayPlug\src\actions\OrderStateAction;
use PayPlug\src\models\classes\Translation;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseOrderStateAction extends TestCase
{
    use FormatDataProvider;

    protected $action;
    protected $dependencies;
    protected $order_state_adapter;
    protected $payplug_orderstate_repository;
    protected $plugin;
    protected $tools_adapter;
    protected $translation;
    protected $validate_adapter;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->plugin = \Mockery::mock('Plugin');

        $this->order_state_adapter = \Mockery::mock('OrderStateAdapter');
        $this->tools_adapter = \Mockery::mock('ToolsAdapter');
        $this->validate_adapter = \Mockery::mock('ValidateAdapter');

        $this->payplug_orderstate_repository = \Mockery::mock('StateRepository');

        $logger = \Mockery::mock('Logger');
        $logger
            ->shouldReceive([
                'addLog' => true,
            ]);
        $this->plugin
            ->shouldReceive([
                'getStateRepository' => $this->payplug_orderstate_repository,
                'getOrderStateAdapter' => $this->order_state_adapter,
                'getLogger' => $logger,
                'getTools' => $this->tools_adapter,
                'getValidate' => $this->validate_adapter,
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation
            ->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });

        $this->action = \Mockery::mock(OrderStateAction::class, [$this->dependencies])->makePartial();
    }
}
