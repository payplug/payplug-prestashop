<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @runTestsInSeparateProcesses
 */
class uninstallTabActionTest extends BaseConfigurationAction
{
    protected $tab_adapter;

    public function setUp()
    {
        parent::setUp();

        $this->tab_adapter = \Mockery::mock('TabAdapter');
        $this->plugin
            ->shouldReceive([
                'getTabAdapter' => $this->tab_adapter,
            ]);
    }

    public function testWhenRetrieveModuleHasntAdminController()
    {
        $module = new \stdClass();
        $this->module->shouldReceive([
            'getInstanceByName' => $module,
        ]);
        $this->assertTrue($this->action->installTabAction());
    }

    public function testWhenAdminControllerCantBeFound()
    {
        $module = new \stdClass();
        $module->adminControllers = [
            [
                'className' => 'AdminController',
            ],
        ];
        $this->module
            ->shouldReceive([
                'getInstanceByName' => $module,
            ]);
        $this->tab_adapter
            ->shouldReceive([
                'getIdFromClassName' => false,
            ]);
        $this->assertTrue($this->action->uninstallTabAction());
    }

    public function testWhenTabCantBeFound()
    {
        $module = new \stdClass();
        $module->adminControllers = [
            [
                'className' => 'AdminController',
            ],
        ];
        $this->module
            ->shouldReceive([
                'getInstanceByName' => $module,
            ]);
        $this->tab_adapter
            ->shouldReceive([
                'getIdFromClassName' => true,
            ]);
        $tab = \Mockery::mock('Tab');
        $this->tab_adapter
            ->shouldReceive([
                'get' => $tab,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => false,
            ]);
        $this->assertFalse($this->action->uninstallTabAction());
    }

    public function testWhenTabCantBeDeleted()
    {
        $module = new \stdClass();
        $module->adminControllers = [
            [
                'className' => 'AdminController',
            ],
        ];
        $this->module
            ->shouldReceive([
                'getInstanceByName' => $module,
            ]);
        $this->tab_adapter
            ->shouldReceive([
                'getIdFromClassName' => true,
            ]);
        $tab = \Mockery::mock('Tab');
        $tab
            ->shouldReceive([
                'delete' => false,
            ]);
        $this->tab_adapter
            ->shouldReceive([
                'get' => $tab,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->assertFalse($this->action->uninstallTabAction());
    }

    public function testWhenTabIsUninstalled()
    {
        $module = new \stdClass();
        $module->adminControllers = [
            [
                'className' => 'AdminController',
            ],
        ];
        $this->module
            ->shouldReceive([
                'getInstanceByName' => $module,
            ]);
        $this->tab_adapter
            ->shouldReceive([
                'getIdFromClassName' => true,
            ]);
        $tab = \Mockery::mock('Tab');
        $tab
            ->shouldReceive([
                'delete' => true,
            ]);
        $this->tab_adapter
            ->shouldReceive([
                'get' => $tab,
            ]);
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->assertTrue($this->action->uninstallTabAction());
    }
}
