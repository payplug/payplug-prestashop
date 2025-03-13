<?php

namespace PayPlug\tests\actions\ValidationAction;

use PayPlug\src\actions\ValidationAction;
use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseValidationAction extends TestCase
{
    use FormatDataProvider;
    protected $action;
    protected $cart_id;
    protected $context;
    protected $dependencies;
    protected $plugin;
    protected $logger;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->cart_id = CartMock::get()->id;
        $this->plugin = \Mockery::mock('Plugin');
        $this->action = \Mockery::mock(ValidationAction::class, [$this->dependencies])->makePartial();
        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');

        $this->dependencies->shouldReceive(
            [
                'getPlugin' => $this->plugin,
            ]
        );

        $this->context = \Mockery::mock('Context');
        $this->context->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $this->context,
            'getLogger' => $this->logger,
            'getTools' => $this->toolsAdapter,
        ]);
    }
}
