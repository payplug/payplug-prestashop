<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class installTabActionTest extends BaseConfigurationAction
{
    protected $tab_adapter;
    protected $languages_adatper;
    protected $tools_adapter;

    public function setUp()
    {
        parent::setUp();

        $this->tab_adapter = \Mockery::mock('TabAdapter');
        $this->languages_adatper = \Mockery::mock('LanguagesAdatper');
        $this->languages_adatper->shouldReceive([
            'getLanguages' => [
                [
                    'id_lang' => 1,
                    'iso_code' => 'en',
                ],
                [
                    'id_lang' => 2,
                    'iso_code' => 'fr',
                ],
                [
                    'id_lang' => 3,
                    'iso_code' => 'it',
                ],
            ],
            'getIDs' => [
                1,
                2,
                3,
            ],
        ]);

        $this->tools_adapter = \Mockery::mock('ToolsAdapter');
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $param) {
                return strtolower($param);
            });

        $this->plugin->shouldReceive([
            'getTabAdapter' => $this->tab_adapter,
            'getLanguage' => $this->languages_adatper,
            'getTools' => $this->tools_adapter,
        ]);
    }

    public function testWhenRetrieveModuleHasntAdminController()
    {
        $this->assertTrue($this->action->installTabAction());
    }

    public function testWhenTabIsAlreadyInstalled()
    {
        $this->module->adminControllers = [
            [
                'className' => 'AdminController',
            ],
        ];
        $this->tab_adapter->shouldReceive([
            'getIdFromClassName' => true,
        ]);
        $this->assertTrue($this->action->installTabAction());
    }

    public function testWhenTabCantBeSaved()
    {
        $this->module->name = 'payplug';
        $this->module->displayName = 'payplug';
        $this->module->adminControllers = [
            [
                'className' => 'AdminController',
            ],
        ];

        $tab = \Mockery::mock('Tab');
        $tab->shouldReceive([
            'add' => false,
        ]);
        $this->tab_adapter->shouldReceive([
            'get' => $tab,
            'getIdFromClassName' => false,
        ]);
        $this->assertFalse($this->action->installTabAction());
    }

    public function testWhenTabIsSaved()
    {
        $this->module->name = 'payplug';
        $this->module->displayName = 'payplug';
        $this->module->adminControllers = [
            [
                'className' => 'AdminController',
            ],
        ];

        $tab = \Mockery::mock('Tab');
        $tab->shouldReceive([
            'add' => true,
        ]);
        $this->tab_adapter->shouldReceive([
            'get' => $tab,
            'getIdFromClassName' => false,
        ]);
        $this->assertTrue($this->action->installTabAction());
    }
}
