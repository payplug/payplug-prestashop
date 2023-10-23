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

    public $action;
    public $dependencies;
    public $payplug_orderstate_repository;
    public $plugin;
    public $toolsAdapter;
    public $translation;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->plugin = \Mockery::mock('Plugin');

        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');

        $this->payplug_orderstate_repository = \Mockery::mock('PayplugOrderStateRepository');

        $this->plugin
            ->shouldReceive([
                'getPayplugOrderStateRepository' => $this->payplug_orderstate_repository,
                'getTools' => $this->toolsAdapter,
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();

        $this->action = \Mockery::mock(OrderStateAction::class, [$this->dependencies])->makePartial();
    }
}
