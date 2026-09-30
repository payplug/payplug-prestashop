<?php

namespace PayPlug\tests\actions\AliasAction;

use PayPlug\src\actions\AliasAction;
use PayPlug\src\utilities\validators\cardValidator;
use PHPUnit\Framework\TestCase;

abstract class BaseAliasAction extends TestCase
{
    protected $action;
    protected $alias_repository;
    protected $customer;
    protected $dependencies;
    protected $logger;
    protected $prestashop_adapter;

    public function setUp(): void
    {
        $this->action = (new \ReflectionClass(AliasAction::class))->newInstanceWithoutConstructor();

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $context_adapter = \Mockery::mock('ContextAdapter');
        $this->alias_repository = \Mockery::mock('AliasRepository');
        $this->logger = \Mockery::mock('Logger');
        $this->prestashop_adapter = \Mockery::mock('PrestashopAdapter17');
        $this->customer = (object) ['id' => 7, 'is_guest' => 0];

        $this->dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $this->dependencies->shouldReceive('getValidators')->andReturn(['card' => new cardValidator()]);
        $this->dependencies->shouldReceive('loadAdapterPresta')->andReturn($this->prestashop_adapter);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $plugin->shouldReceive('getContext')->andReturn($context_adapter);
        $plugin->shouldReceive('getLogger')->andReturn($this->logger);
        $context_adapter->shouldReceive('get')->andReturnUsing(function () {
            return (object) ['customer' => $this->customer];
        });
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.models.repositories.alias')->andReturn($this->alias_repository);
        $this->logger->shouldReceive('addLog')->byDefault();

        $this->action->dependencies = $this->dependencies;
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function aliasRow(array $overrides = [])
    {
        return array_merge([
            'id_payplug_alias' => '1',
            'id_customer' => '7',
            'alias_id' => 'alias_abc',
            'currency' => 'usd',
            'identifier' => 'ident_usd',
            'brand' => 'visa',
            'last4' => '0001',
            'exp_month' => '12',
            'exp_year' => '2099',
            'date_add' => '2026-09-28 10:00:00',
        ], $overrides);
    }
}
