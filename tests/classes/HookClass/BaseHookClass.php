<?php

namespace PayPlug\tests\classes\HookClass;

use PayPlug\classes\HookClass;
use PHPUnit\Framework\TestCase;

abstract class BaseHookClass extends TestCase
{
    protected $alias_action;
    protected $card_action;
    protected $config_class;
    protected $dependencies;
    protected $hook;
    protected $plugin;
    protected $translation;

    public function setUp(): void
    {
        $this->alias_action = \Mockery::mock('AliasAction');
        $this->card_action = \Mockery::mock('CardAction');
        $this->config_class = \Mockery::mock('ConfigClass');
        $this->translation = \Mockery::mock('TranslationClass');
        $this->translation->shouldReceive('l')->andReturnUsing(function ($key) {
            return $key;
        });

        $module = \Mockery::mock('Module');
        $module->shouldReceive('getService')->with('payplug.action.alias')->andReturn($this->alias_action);
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $context_adapter = \Mockery::mock('ContextAdapter');
        $context_adapter->shouldReceive('get')->andReturn(new \stdClass());

        // The constructor reads every collaborator off the plugin; only the ones the GDPR
        // hooks use are set explicitly, the others resolve to null (unused here).
        $this->plugin = \Mockery::mock('Plugin')->shouldIgnoreMissing();
        $this->plugin->shouldReceive([
            'getCardAction' => $this->card_action,
            'getContext' => $context_adapter,
            'getModule' => $module_adapter,
            'getTranslationClass' => $this->translation,
        ]);

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->dependencies->configClass = $this->config_class;
        $this->dependencies->shouldReceive('getPlugin')->andReturn($this->plugin);

        $this->hook = new HookClass($this->dependencies);
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }
}
