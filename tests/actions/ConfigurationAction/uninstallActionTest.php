<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use PayPlug\classes\MyLogPHP;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @runTestsInSeparateProcesses
 */
class uninstallActionTest extends BaseConfigurationAction
{
    private $card_action;
    private $constant;
    private $entity_repository;

    public function setUp()
    {
        parent::setUp();

        $txt_log = \Mockery::mock(MyLogPHP::class);
        $txt_log->shouldReceive([
            'info' => 'log str',
        ]);

        $this->card_action = \Mockery::mock('CardAction');

        $this->constant = \Mockery::mock('Constant');
        $this->constant->shouldReceive('get')
            ->with('_PS_MODULE_DIR_')
            ->andReturn('module_path');

        $this->entity_repository = \Mockery::mock('EntityRepository');

        $this->plugin->shouldReceive([
            'getCardAction' => $this->card_action,
            'getConstant' => $this->constant,
            'getEntityRepository' => $this->entity_repository,
        ]);
    }

    public function testWhenCardCantBeUninstalled()
    {
        $this->card_action->shouldReceive([
            'uninstallAction' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Uninstall failed: Unable to delete saved cards.',
            ],
            $this->action->uninstallAction()
        );
    }

    public function testWhenConfigurationCantBeUninstalled()
    {
        $this->card_action->shouldReceive([
            'uninstallAction' => true,
        ]);
        $this->configuration_class->shouldReceive([
            'deleteAll' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Uninstall failed: Can\'t remove module configuration',
            ],
            $this->action->uninstallAction()
        );
    }

    public function testWhenModuleTableCantBeDropped()
    {
        $this->card_action->shouldReceive([
            'uninstallAction' => true,
        ]);
        $this->configuration_class->shouldReceive([
            'deleteAll' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'uninstall' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Uninstall failed: Drop module table.',
            ],
            $this->action->uninstallAction()
        );
    }

    public function testWhenModuleTabCantBeUninstalled()
    {
        $this->card_action->shouldReceive([
            'uninstallAction' => true,
        ]);
        $this->configuration_class->shouldReceive([
            'deleteAll' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'uninstall' => true,
        ]);
        $this->action->shouldReceive([
            'uninstallTabAction' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Uninstall failed: Tab.',
            ],
            $this->action->uninstallAction()
        );
    }

    public function testWhenUninstallIsComplete()
    {
        $this->card_action->shouldReceive([
            'uninstallAction' => true,
        ]);
        $this->configuration_class->shouldReceive([
            'deleteAll' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'uninstall' => true,
        ]);
        $this->action->shouldReceive([
            'uninstallTabAction' => true,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Uninstall successful',
            ],
            $this->action->uninstallAction()
        );
    }
}
