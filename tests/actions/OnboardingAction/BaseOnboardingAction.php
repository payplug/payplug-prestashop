<?php

namespace PayPlug\tests\actions\OnboardingAction;

use PayPlug\src\actions\OnboardingAction;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseOnboardingAction extends TestCase
{
    public $configurationClass;
    public $dependencies;

    protected function setUp()
    {
        $this->configurationClass = \Mockery::mock('ConfigurationClass');
        $this->configurationClass
            ->shouldReceive([
                'get' => true,
            ]);

        $this->oney = \Mockery::mock('Oney');
        $this->plugin = \Mockery::mock('Plugin');

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->plugin
            ->shouldReceive([
                'getConfigurationClass' => $this->configurationClass,
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);
        $this->dependencies
            ->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                return $key;
            })
        ;
        $this->dependencies->name = 'payplug';

        $this->action = \Mockery::mock(OnboardingAction::class, [$this->dependencies])->makePartial();
    }
}
