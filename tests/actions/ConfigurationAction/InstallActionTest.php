<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use PayPlug\classes\MyLogPHP;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class installActionTest extends BaseConfigurationAction
{
    public $configuration_helper;
    public $constant;
    public $domain_association_action;
    public $files_helper;
    public $order_state_action;
    public $entity_repository;
    public $txt_log;

    public function setUp(): void
    {
        parent::setUp();

        $this->txt_log = \Mockery::mock(MyLogPHP::class);
        $this->txt_log->shouldReceive([
            'info' => 'log str',
        ]);
        $this->action->shouldReceive('getInstallLog')->andReturn($this->txt_log);

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

        $this->domain_association_action = \Mockery::mock('ApplePayDomainAssociationAction');
        $this->module->shouldReceive('getService')
            ->with('payplug.action.apple_pay_domain_association')
            ->andReturn($this->domain_association_action)
            ->byDefault();

        $shop = \Mockery::mock('Shop');
        $shop->shouldReceive([
            'isFeatureActive' => true,
            'setContext' => true,
        ]);

        $this->order_state_action = \Mockery::mock('OrderStateAction');

        $this->plugin->shouldReceive([
            'getConstant' => $this->constant,
            'getOrderStateAction' => $this->order_state_action,
            'getEntityRepository' => $this->entity_repository,
            'getShop' => $shop,
        ]);

        $this->configuration_helper = \Mockery::mock('ConfigurationHelper');
        $this->files_helper = new class() {
            public static function clean()
            {
                return true;
            }
        };
        $this->dependencies->shouldReceive([
            'getHelpers' => [
                'configuration' => $this->configuration_helper,
                'files' => $this->files_helper,
            ],
        ]);
    }

    public function testWhenPHPRequirementsAreNotSatisfied()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => false,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: PHP Requirement.',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenCurlRequirementsAreNotSatisfied()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => false,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: cURL Requirement.',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenOpenSSLRequirementsAreNotSatisfied()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => false,
                ],
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: OpenSSL Requirement.',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenConfigurationCantBeInitialized()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: Set configuration',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenQueryCantBeInitialized()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'initialize' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: Install SQL tables.',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenOrderStateCantBeInstalled()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'initialize' => true,
        ]);
        $this->action->shouldReceive([
            'installOrderStateAction' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: Install order state.',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenOrderStateTypeCantBeInstalled()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'initialize' => true,
        ]);
        $this->action->shouldReceive([
            'installOrderStateAction' => true,
        ]);
        $this->order_state_action->shouldReceive([
            'installTypeAction' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: Create order states type.',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenTabCantBeInstalled()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'initialize' => true,
        ]);
        $this->action->shouldReceive([
            'installOrderStateAction' => true,
            'installTabAction' => false,
        ]);
        $this->order_state_action->shouldReceive([
            'installTypeAction' => true,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: Install Tab.',
            ],
            $this->action->installAction()
        );
    }

    public function testWhenHookCantBeInstalled()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'initialize' => true,
        ]);
        $this->action->shouldReceive([
            'installHookAction' => false,
            'installOrderStateAction' => true,
            'installTabAction' => true,
        ]);
        $this->order_state_action->shouldReceive([
            'installTypeAction' => true,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: Install hook.',
            ],
            $this->action->installAction()
        );
    }

    public function testLoadsTheUnifiedAliasRepositoryBeforeCreatingTables()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => true,
        ]);
        $this->module->shouldReceive('getService')
            ->once()
            ->with('payplug.models.repositories.unified_alias')
            ->andReturn(\Mockery::mock('UnifiedAliasRepository'));
        $this->entity_repository->shouldReceive([
            'initialize' => false,
        ]);

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Install failed: Install SQL tables.',
            ],
            $this->action->installAction()
        );
    }

    public function testSynchronisesTheApplePayDomainAssociationOnceInstalled()
    {
        $this->givenAnInstallThatSucceeds();
        $this->domain_association_action->shouldReceive('ensureStoredAction')->once()->andReturn(true);
        $this->txt_log->shouldNotReceive('error');

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Install successful',
            ],
            $this->action->installAction()
        );
    }

    public function testCompletesTheInstallAndLogsAnErrorWhenTheSynchronisationCannotBeAttempted()
    {
        $this->givenAnInstallThatSucceeds();
        $this->domain_association_action->shouldReceive('ensureStoredAction')->once()->andReturn(false);
        $this->txt_log->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/^Apple Pay domain association could not be synchronised: /'));

        $this->assertTrue($this->action->installAction()['result']);
    }

    public function testCompletesTheInstallAndLogsAnErrorWhenTheActionCannotBeBuilt()
    {
        $this->givenAnInstallThatSucceeds();
        $this->module->shouldReceive('getService')
            ->with('payplug.action.apple_pay_domain_association')
            ->andThrow(new \RuntimeException('container broken'));
        $this->txt_log->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/could not be synchronised: RuntimeException: container broken$/'));

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Install successful',
            ],
            $this->action->installAction()
        );
    }

    public function testCompletesTheInstallAndLogsAnErrorWhenTheActionIsNotRegistered()
    {
        $this->givenAnInstallThatSucceeds();
        $this->module->shouldReceive('getService')
            ->with('payplug.action.apple_pay_domain_association')
            ->andReturn(null);
        $this->txt_log->shouldReceive('error')
            ->once()
            ->with(\Mockery::pattern('/^Apple Pay domain association could not be synchronised: /'));

        $this->assertTrue($this->action->installAction()['result']);
    }

    public function testDoesNotSynchroniseTheApplePayDomainAssociationWhenTheInstallFails()
    {
        $this->givenAnInstallThatSucceeds();
        $this->action->shouldReceive('installHookAction')->andReturn(false);
        $this->domain_association_action->shouldNotReceive('ensureStoredAction');

        $this->assertFalse($this->action->installAction()['result']);
    }

    private function givenAnInstallThatSucceeds()
    {
        $this->configuration_helper->shouldReceive([
            'getRequirements' => [
                'php' => [
                    'up2date' => true,
                ],
                'curl' => [
                    'up2date' => true,
                ],
                'openssl' => [
                    'up2date' => true,
                ],
            ],
        ]);
        $this->configuration_class->shouldReceive([
            'initialize' => true,
        ]);
        $this->entity_repository->shouldReceive([
            'initialize' => true,
        ]);
        $this->action->shouldReceive('installOrderStateAction')->andReturn(true);
        $this->action->shouldReceive('installTabAction')->andReturn(true);
        $this->action->shouldReceive('installHookAction')->andReturn(true)->byDefault();
        $this->order_state_action->shouldReceive([
            'installTypeAction' => true,
        ]);
    }
}
