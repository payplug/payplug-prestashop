<?php

namespace PayPlug\tests\models\classes\Merchant;

use PayPlug\src\models\classes\Merchant;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseMerchant extends TestCase
{
    use FormatDataProvider;

    protected $dependencies;
    protected $logger_adapter;
    protected $class;
    protected $plugin;
    protected $module;
    protected $module_adapter;
    protected $api_service;

    public function setUp()
    {
        $this->dependencies = \Mockery::mock('DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->logger_adapter = \Mockery::mock('LoggerAdapter');
        $this->plugin = \Mockery::mock('Plugin');

        $this->logger_adapter->shouldReceive([
            'addLog' => true,
        ]);

        $this->api_service = \Mockery::mock('ApiService');
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
            'getLogger' => $this->logger_adapter,
            'getModule' => $this->module_adapter,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->class = \Mockery::mock(Merchant::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->dependencies = $this->dependencies;
    }
}
