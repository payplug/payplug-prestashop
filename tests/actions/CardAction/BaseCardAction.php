<?php

namespace PayPlug\tests\actions\CardAction;

use PayPlug\src\actions\CardAction;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseCardAction extends TestCase
{
    use FormatDataProvider;

    public $action;
    public $api_service;
    public $card_repository;
    public $card_validator;
    public $configuration_class;
    public $dependencies;
    public $logger;
    public $plugin;
    public $module;
    public $module_adapter;

    public function setUp()
    {
        $this->api_service = \Mockery::mock('ApiService');
        $this->card_repository = \Mockery::mock('CardRepository');
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->context = \Mockery::mock('Context');
        $this->context->shouldReceive([
            'get' => ContextMock::get(),
        ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->plugin = \Mockery::mock('Plugin');

        $this->module = \Mockery::mock('Module');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.service.api')
            ->andReturn($this->api_service);

        $this->plugin->shouldReceive([
            'getCardRepository' => $this->card_repository,
            'getConfigurationClass' => $this->configuration_class,
            'getContext' => $this->context,
            'getLogger' => $this->logger,
            'getModule' => $this->module_adapter,
        ]);

        $this->card_validator = \Mockery::mock('CardValidator');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
            'getValidators' => [
                'card' => $this->card_validator,
            ],
        ]);

        $this->action = \Mockery::mock(CardAction::class, [$this->dependencies])->makePartial();
    }
}
