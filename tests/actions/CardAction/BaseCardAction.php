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
    public $card_repository;
    public $card_validator;
    public $configuration_class;
    public $dependencies;
    public $plugin;

    protected function setUp()
    {
        $this->card_repository = \Mockery::mock('CardRepository');
        $this->configuration_class = \Mockery::mock('ConfigurationClass');
        $this->context = \Mockery::mock('Context');
        $this->context
            ->shouldReceive([
                'get' => ContextMock::get(),
            ]);

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getCardRepository' => $this->card_repository,
                'getConfigurationClass' => $this->configuration_class,
                'getContext' => $this->context,
            ]);

        $this->card_validator = \Mockery::mock('CardValidator');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->apiClass = \Mockery::mock('ApiClass');
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => [
                  'card' => $this->card_validator,
                ],
            ]);

        $this->action = \Mockery::mock(CardAction::class, [$this->dependencies])->makePartial();
    }
}
