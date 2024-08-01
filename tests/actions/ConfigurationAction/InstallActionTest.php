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
class installActionTest extends BaseConfigurationAction
{
    private $configuration_helper;
    private $constant;
    private $files_helper;
    private $order_state_action;
    private $entity_repository;

    public function setUp()
    {
        parent::setUp();

        $txt_log = \Mockery::mock(MyLogPHP::class);
        $txt_log
            ->shouldReceive([
                'info' => 'log str',
            ]);

        $this->constant = \Mockery::mock('Constant');
        $this->constant
            ->shouldReceive('get')
            ->with('_PS_MODULE_DIR_')
            ->andReturn('module_path');

        $this->entity_repository = \Mockery::mock('EntityRepository');

        $shop = \Mockery::mock('Shop');
        $shop->shouldReceive([
            'isFeatureActive' => true,
            'setContext' => true,
        ]);

        $this->order_state_action = \Mockery::mock('OrderStateAction');

        $this->plugin
            ->shouldReceive([
                'getConstant' => $this->constant,
                'getOrderStateAction' => $this->order_state_action,
                'getEntityRepository' => $this->entity_repository,
                'getShop' => $shop,
            ]);

        $this->configuration_helper = \Mockery::mock('ConfigurationHelper');
        $this->files_helper = \Mockery::mock('FilesHelper');
        $this->dependencies
            ->shouldReceive([
                'getHelpers' => [
                    'configuration' => $this->configuration_helper,
                    'files' => $this->files_helper,
                ],
            ]);
    }

    public function testWhenPHPRequirementsAreNotSatisfied()
    {
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_class
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_class
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->entity_repository
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_class
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->entity_repository
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->action
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_class
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->entity_repository
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->action
            ->shouldReceive([
                'installOrderStateAction' => true,
            ]);
        $this->order_state_action
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_class
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->entity_repository
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->action
            ->shouldReceive([
                'installOrderStateAction' => true,
                'installTabAction' => false,
            ]);
        $this->order_state_action
            ->shouldReceive([
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
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_class
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->entity_repository
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->action
            ->shouldReceive([
                'installHookAction' => false,
                'installOrderStateAction' => true,
                'installTabAction' => true,
            ]);
        $this->order_state_action
            ->shouldReceive([
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

    public function testWhenInstallIsComplete()
    {
        $this->configuration_helper
            ->shouldReceive([
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
        $this->configuration_class
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->entity_repository
            ->shouldReceive([
                'initialize' => true,
            ]);
        $this->action
            ->shouldReceive([
                'installHookAction' => true,
                'installOrderStateAction' => true,
                'installTabAction' => true,
            ]);
        $this->order_state_action
            ->shouldReceive([
                'installTypeAction' => true,
            ]);
        $this->files_helper
            ->shouldReceive([
                'clean' => true,
            ]);

        $this->assertSame(
            [
                'result' => true,
                'message' => 'Install successful',
            ],
            $this->action->installAction()
        );
    }
}
