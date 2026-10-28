<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;

/**
 * @group unit
 * @group class
 * @group apirest_class
 */
class dispatchTest extends BaseApiRest
{
    public $module;
    public $module_adapter;

    public function setUp(): void
    {
        parent::setUp();

        $this->module = \Mockery::mock('Module');
        $this->module_adapter = \Mockery::mock('ModuleAdapter');
        $this->module_adapter->shouldReceive([
            'getInstanceByName' => $this->module,
        ]);
        $this->plugin->shouldReceive([
            'getModule' => $this->module_adapter,
        ]);
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $action
     */
    public function testWhenGivenActionWithInvalidStringFormat($action)
    {
        $this->assertSame(
            [],
            $this->class->dispatch($action)
        );
    }

    public function testWhenGivenActionIsLogin()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action->shouldReceive([
            'loginAction' => $response,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 'file_get_contents',
        ]);

        $action = 'login';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
        );
    }

    public function testWhenGivenActionIsLogout()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action->shouldReceive([
            'logoutAction' => $response,
        ]);
        $action = 'logout';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
        );
    }

    public function testWhenGivenActionIsSave()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action->shouldReceive([
            'saveAction' => $response,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 'file_get_contents',
        ]);

        $action = 'save';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
        );
    }

    public function testWhenGivenActionIsInit()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action->shouldReceive([
            'renderConfiguration' => $response,
        ]);
        $action = 'init';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
        );
    }

    public function testWhenGivenActionIsApplePayResync()
    {
        $diagnostics = [
            'state' => 'synced',
            'last_attempt_at' => 20,
            'last_success_at' => 20,
            'last_error' => null,
            'root_file_present' => false,
        ];
        $domain_association_action = \Mockery::mock('ApplePayDomainAssociationAction');
        $domain_association_action->shouldReceive('refreshAction')
            ->once()
            ->andReturn(['success' => true, 'status' => 'updated', 'error' => null]);
        $domain_association_action->shouldReceive('getDiagnosticsAction')->once()->andReturn($diagnostics);
        $this->mockDomainAssociationAction($domain_association_action);

        $this->assertSame(
            ['success' => true, 'status' => 'updated', 'diagnostics' => $diagnostics],
            $this->class->dispatch('apple_pay_resync')
        );
    }

    public function testWhenGivenActionIsApplePayResyncAndTheRefreshFails()
    {
        $diagnostics = [
            'state' => 'not_synced',
            'last_attempt_at' => 30,
            'last_success_at' => null,
            'last_error' => 'Source unreachable: &lt;b&gt;boom&lt;/b&gt;',
            'root_file_present' => false,
        ];
        $domain_association_action = \Mockery::mock('ApplePayDomainAssociationAction');
        $domain_association_action->shouldReceive('refreshAction')
            ->once()
            ->andReturn(['success' => false, 'status' => 'failed', 'error' => 'Source unreachable: <b>boom</b>']);
        $domain_association_action->shouldReceive('getDiagnosticsAction')->once()->andReturn($diagnostics);
        $this->mockDomainAssociationAction($domain_association_action);

        $this->assertSame(
            ['success' => false, 'status' => 'failed', 'diagnostics' => $diagnostics],
            $this->class->dispatch('apple_pay_resync')
        );
    }

    private function mockDomainAssociationAction($domain_association_action)
    {
        $this->plugin->getModule()
            ->getInstanceByName('payplug')
            ->shouldReceive('getService')
            ->with('payplug.action.apple_pay_domain_association')
            ->andReturn($domain_association_action);
    }
}
