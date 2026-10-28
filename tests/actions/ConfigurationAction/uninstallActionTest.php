<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use PayPlug\classes\MyLogPHP;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class uninstallActionTest extends BaseConfigurationAction
{
    public $card_action;
    public $constant;
    public $domain_association_storage;
    public $entity_repository;
    public $factory;
    public $txt_log;

    public function setUp(): void
    {
        parent::setUp();

        $this->txt_log = \Mockery::mock(MyLogPHP::class);
        $this->txt_log->shouldReceive([
            'info' => 'log str',
        ]);
        $this->action->shouldReceive('getInstallLog')->andReturn($this->txt_log);

        $this->card_action = \Mockery::mock('CardAction');

        $this->constant = \Mockery::mock('Constant');
        $this->constant->shouldReceive('get')
            ->with('_PS_MODULE_DIR_')
            ->andReturn('module_path');

        $this->entity_repository = \Mockery::mock('EntityRepository');

        $this->module->shouldReceive('getService')
            ->with('payplug.models.repositories.unified_operation')
            ->andReturn(\Mockery::mock('UnifiedOperationRepository'));

        $this->module->shouldReceive('getService')
            ->with('payplug.models.repositories.unified_lock')
            ->andReturn(\Mockery::mock('UnifiedLockRepository'));

        $this->module->shouldReceive('getService')
            ->with('payplug.models.repositories.unified_alias')
            ->andReturn(\Mockery::mock('UnifiedAliasRepository'))
            ->byDefault();

        $this->module->shouldReceive('getService')
            ->with('payplug.models.repositories.unified_refund')
            ->andReturn(\Mockery::mock('UnifiedRefundRepository'));

        $this->domain_association_storage = \Mockery::mock('UnifiedDomainAssociationStorage');
        $this->domain_association_storage->shouldReceive('purge')->andReturn(true)->byDefault();
        $this->factory = \Mockery::mock('UnifiedApiPaymentServiceFactory');
        $this->factory->shouldReceive('createDomainAssociationStorage')
            ->andReturn($this->domain_association_storage)
            ->byDefault();
        $this->module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn($this->factory)
            ->byDefault();

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

    public function testLoadsTheUnifiedAliasRepositoryBeforeDroppingTables()
    {
        $this->card_action->shouldReceive([
            'uninstallAction' => true,
        ]);
        $this->configuration_class->shouldReceive([
            'deleteAll' => true,
        ]);
        $this->module->shouldReceive('getService')
            ->once()
            ->with('payplug.models.repositories.unified_alias')
            ->andReturn(\Mockery::mock('UnifiedAliasRepository'));
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

    public function testRemovesTheApplePayDomainAssociation()
    {
        $this->givenAnUninstallThatSucceeds();
        $this->domain_association_storage->shouldReceive('purge')->once()->andReturn(true);
        $this->txt_log->shouldNotReceive('error');

        $this->assertTrue($this->action->uninstallAction()['result']);
    }

    public function testCompletesTheUninstallAndLogsAnErrorWhenTheApplePayDomainAssociationCannotBeRemoved()
    {
        $this->givenAnUninstallThatSucceeds();
        $this->domain_association_storage->shouldReceive('purge')->once()->andReturn(false);
        $this->txt_log->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/^Apple Pay domain association could not be removed: /'));

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Uninstall successful',
            ],
            $this->action->uninstallAction()
        );
    }

    public function testCompletesTheUninstallAndLogsAnErrorWhenThePurgeThrows()
    {
        $this->givenAnUninstallThatSucceeds();
        $this->domain_association_storage->shouldReceive('purge')
            ->once()
            ->andThrow(new \RuntimeException('configuration table locked'));
        $this->txt_log->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/could not be removed: RuntimeException: configuration table locked$/'));

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Uninstall successful',
            ],
            $this->action->uninstallAction()
        );
    }

    public function testCompletesTheUninstallAndLogsAnErrorWhenTheFactoryCannotBeBuilt()
    {
        $this->givenAnUninstallThatSucceeds();
        $this->factory->shouldReceive('createDomainAssociationStorage')
            ->once()
            ->andThrow(new \TypeError('dependencies missing'));
        $this->txt_log->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/could not be removed: TypeError: dependencies missing$/'));

        $this->assertTrue($this->action->uninstallAction()['result']);
    }

    public function testCompletesTheUninstallAndLogsAnErrorWhenTheFactoryIsNotRegistered()
    {
        $this->givenAnUninstallThatSucceeds();
        $this->module->shouldReceive('getService')
            ->with('payplug.unified.payment_service_factory')
            ->andReturn(null);
        $this->domain_association_storage->shouldNotReceive('purge');
        $this->txt_log->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/^Apple Pay domain association could not be removed: /'));

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Uninstall successful',
            ],
            $this->action->uninstallAction()
        );
    }

    public function testKeepsTheApplePayDomainAssociationWhenTheConfigurationCannotBeRemoved()
    {
        $this->card_action->shouldReceive([
            'uninstallAction' => true,
        ]);
        $this->configuration_class->shouldReceive([
            'deleteAll' => false,
        ]);
        $this->domain_association_storage->shouldNotReceive('purge');

        $this->assertFalse($this->action->uninstallAction()['result']);
    }

    private function givenAnUninstallThatSucceeds()
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
    }
}
