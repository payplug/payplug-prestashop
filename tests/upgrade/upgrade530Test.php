<?php

namespace PayPlug\tests\upgrade;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\tests\ReleasesTestProperties;
use PHPUnit\Framework\TestCase;

require_once dirname(dirname(__DIR__)) . '/upgrade/upgrade-5.3.0.php';

/**
 * @group unit
 * @group upgrade
 */
class upgrade530Test extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ReleasesTestProperties;

    protected $action;
    protected $api_service;
    protected $configuration;
    protected $dependencies;
    protected $failing_writes;
    protected $logs;
    protected $module;
    protected $global_writes;
    protected $saved;
    protected $saved_scoped;
    protected $scoped;
    protected $stored;
    protected $throwing;

    public function setUp(): void
    {
        $this->stored = [
            'scalapay_custom_min_amounts' => '',
            'scalapay_custom_max_amounts' => '',
        ];
        $this->failing_writes = [];
        $this->global_writes = [];
        $this->logs = [];
        $this->saved = [];
        $this->saved_scoped = [];
        $this->scoped = [];
        $this->throwing = [];

        $this->configuration = \Mockery::mock('Configuration');
        $read = function ($key, $id_shop_group = 0, $id_shop = 0) {
            if (in_array($key, $this->throwing, true)) {
                throw new \TypeError('Unexpected value for ' . $key);
            }
            if ($id_shop && isset($this->scoped[$key][$id_shop_group . '/' . $id_shop])) {
                return $this->scoped[$key][$id_shop_group . '/' . $id_shop];
            }

            return array_key_exists($key, $this->stored) ? $this->stored[$key] : false;
        };
        $this->configuration->shouldReceive('getValue')->andReturnUsing($read);
        $this->configuration->shouldReceive('getScopedValue')->andReturnUsing($read);
        $this->configuration->shouldReceive('set')
            ->andReturnUsing(function ($key, $value, $id_shop_group = null, $id_shop = null) {
                if (in_array($key, $this->failing_writes, true)) {
                    return false;
                }
                if ($id_shop) {
                    $this->saved_scoped[$id_shop_group . '/' . $id_shop][$key] = $value;
                    $this->scoped[$key][$id_shop_group . '/' . $id_shop] = $value;

                    return true;
                }
                if (0 === $id_shop_group && 0 === $id_shop) {
                    $this->global_writes[] = $key;
                }
                $this->saved[$key] = $value;
                $this->stored[$key] = $value;

                return true;
            });
        $this->configuration->shouldReceive('getName')
            ->andReturnUsing(function ($key) {
                return 'PAYPLUG_' . strtoupper($key);
            });

        $order_state_repository = \Mockery::mock('OrderStateRepository');
        $order_state_repository->shouldReceive('getConfigurationsByName')
            ->andReturnUsing(function ($name) {
                $rows = [];
                foreach ($this->stored as $key => $value) {
                    if ('PAYPLUG_' . strtoupper($key) === $name) {
                        $rows[] = ['id_shop_group' => null, 'id_shop' => null, 'value' => $value];
                    }
                }
                foreach ($this->scoped as $key => $values) {
                    foreach ($values as $scope => $value) {
                        if ('PAYPLUG_' . strtoupper($key) === $name) {
                            list($id_shop_group, $id_shop) = explode('/', $scope);
                            $rows[] = ['id_shop_group' => $id_shop_group, 'id_shop' => $id_shop, 'value' => $value];
                        }
                    }
                }

                return $rows;
            });

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')->andReturnUsing(function ($message, $level = 'info') {
            $this->logs[] = ['level' => $level, 'message' => $message];

            return true;
        });

        $this->api_service = \Mockery::mock('ApiService');
        $this->api_service->shouldReceive('getAccount')->andReturn(['is_live' => false])->byDefault();
        $this->action = \Mockery::mock('ApplePayDomainAssociationAction');
        $this->action->shouldReceive('ensureStoredAction')->andReturn(true)->byDefault();
        $this->module = \Mockery::mock('Payplug');
        $this->module->shouldReceive('getService')
            ->with('payplug.utilities.service.api')
            ->andReturn($this->api_service);
        $this->module->shouldReceive('getService')
            ->with('payplug.action.apple_pay_domain_association')
            ->andReturn($this->action);
        $this->module->shouldReceive('registerHook')->with('actionDispatcherBefore')->andReturn(true)->byDefault();
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module_adapter->shouldReceive('getInstanceByName')
            ->with('payplug')
            ->andReturn($this->module);

        $shop_adapter = \Mockery::mock('ShopAdapter');
        $shop_adapter->shouldReceive('get')
            ->andReturnUsing(function ($id_shop) {
                $shop = new \stdClass();
                $shop->id_shop_group = 2 === $id_shop ? 1 : null;

                return $shop;
            });

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getLogger' => $logger,
            'getModule' => $module_adapter,
            'getOrderStateRepository' => $order_state_repository,
            'getShop' => $shop_adapter,
        ]);

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $plugin,
            'getHelpers' => [
                'amount' => new AmountHelper($this->dependencies),
            ],
        ]);
        $this->module->payplug_dependencies = $this->dependencies;
    }

    protected function tearDown(): void
    {
        $this->releaseTestProperties();
        parent::tearDown();
    }

    public function testTheAccountIsRequestedOnceThroughTheAccountPath()
    {
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->withNoArgs()
            ->andReturn(['is_live' => false]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));
    }

    public function testOnlyTheMerchantSettingsKeyIsInitialised()
    {
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        upgrade_module_5_3_0($this->getModuleObject());

        $this->assertSame(['apm_custom_settings' => '{}'], $this->saved);
        $this->assertSame(['apm_custom_settings'], $this->global_writes);
    }

    public function testExistingMerchantSettingsAreKept()
    {
        $this->stored['apm_custom_settings'] = '{"satispay":{"countries":["IT"]}}';
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        upgrade_module_5_3_0($this->getModuleObject());

        $this->assertSame([], $this->saved);
    }

    public function testTheUpgradeSucceedsWhenTheAccountCannotBeFetched()
    {
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->andReturn([]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));
    }

    public function testTheUpgradeSucceedsWhenTheAccountRequestThrows()
    {
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->andThrow(new \Exception('Service unavailable', 503));

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));
    }

    public function testTheLegacyScalapayAmountsAreImportedBeforeTheAccountRequest()
    {
        $this->stored['apm_custom_settings'] = '{}';
        $this->stored['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->stored['amounts'] = '{"scalapay":{"min":"EUR:500","max":"EUR:400000"}}';
        $settings_on_request = null;
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->andReturnUsing(function () use (&$settings_on_request) {
                $settings_on_request = isset($this->saved['apm_custom_settings']) ? $this->saved['apm_custom_settings'] : null;

                return ['is_live' => false];
            });

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));
        $this->assertSame('{"scalapay":{"min":{"EUR":1000}}}', $settings_on_request);
    }

    public function testTheUpgradeSucceedsWhenTheAccountRequestRaisesAnError()
    {
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->andThrow(new \Error('Call to a member function on null'));

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));
    }

    public function testTheUpgradeSucceedsWhenTheLegacyImportsRaiseAnError()
    {
        $this->throwing = ['scalapay_custom_min_amounts', 'bancontact_country'];
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->andReturn(['is_live' => false]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));
    }

    public function testBelgiumOnlyIsCarriedOverToTheBancontactCountries()
    {
        $this->stored['apm_custom_settings'] = '{}';
        $this->stored['bancontact_country'] = '1';
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));

        $this->assertSame(['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}'], $this->saved);
        $this->assertSame('1', $this->stored['bancontact_country']);
    }

    public function testNoBancontactCountryIsWrittenWhenBelgiumOnlyWasNotTicked()
    {
        $this->stored['apm_custom_settings'] = '{}';
        $this->stored['bancontact_country'] = '0';
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));

        $this->assertSame([], $this->saved);
    }

    public function testStoredBancontactCountriesAreNotOverwritten()
    {
        $this->stored['apm_custom_settings'] = '{"bancontact":{"countries":["FR"]}}';
        $this->stored['bancontact_country'] = '1';
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));

        $this->assertSame([], $this->saved);
    }

    public function testTheScalapayAmountsAndTheBelgiumOnlySettingAreBothCarriedOver()
    {
        $this->stored['apm_custom_settings'] = '{}';
        $this->stored['bancontact_country'] = '1';
        $this->stored['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->stored['amounts'] = '{"scalapay":{"min":"EUR:500","max":"EUR:400000"}}';
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));

        $this->assertSame(
            '{"bancontact":{"countries":["BE"]},"scalapay":{"min":{"EUR":1000}}}',
            $this->saved['apm_custom_settings']
        );
    }

    public function testBelgiumOnlyTickedOnAShopIsCarriedOverOnThatShopOnly()
    {
        $this->stored['bancontact_country'] = '0';
        $this->scoped['bancontact_country']['1/2'] = '1';
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));

        $this->assertSame(['apm_custom_settings' => '{}'], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}']],
            $this->saved_scoped
        );
    }

    public function testBelgiumOnlyIsKeptOnAShopWhoseOwnScalapayAmountsAreImportedFirst()
    {
        $this->stored['bancontact_country'] = '1';
        $this->stored['amounts'] = '{"scalapay":{"min":"EUR:500","max":"EUR:400000"}}';
        $this->scoped['scalapay_custom_min_amounts']['1/2'] = 'EUR:1000';
        $this->scoped['scalapay_custom_max_amounts']['1/2'] = 'EUR:150000';
        $this->api_service->shouldReceive([
            'getAccount' => ['is_live' => false],
        ]);

        $this->assertTrue(upgrade_module_5_3_0($this->getModuleObject()));

        $this->assertSame('{"bancontact":{"countries":["BE"]}}', $this->stored['apm_custom_settings']);
        $this->assertSame(
            '{"bancontact":{"countries":["BE"]},"scalapay":{"min":{"EUR":1000},"max":{"EUR":150000}}}',
            $this->scoped['apm_custom_settings']['1/2']
        );
    }

    public function testTheUpgradeFailsWhenTheMerchantSettingsKeyCannotBeInitialised()
    {
        $this->failing_writes = ['apm_custom_settings'];
        $this->module->shouldReceive('registerHook')->once()->with('actionDispatcherBefore')->andReturn(true);
        $this->action->shouldReceive('ensureStoredAction')->once()->andReturn(true);

        $this->assertFalse(upgrade_module_5_3_0($this->getModuleObject()));
    }

    public function testRegistersTheDispatcherHookAndStoresTheFile()
    {
        $this->module->shouldReceive('registerHook')->once()->with('actionDispatcherBefore')->andReturn(true);
        $this->action->shouldReceive('ensureStoredAction')->once()->andReturn(true);

        $this->assertTrue(upgrade_module_5_3_0($this->module));
    }

    public function testRegistersTheDispatcherHookOnEveryShop()
    {
        $received = [];
        $this->module->shouldReceive('registerHook')->once()->andReturnUsing(function () use (&$received) {
            $received = func_get_args();

            return true;
        });

        $this->assertTrue(upgrade_module_5_3_0($this->module));
        // Hook::registerHook() registers on Shop::getCompleteListOfShopsID() when no shop list is given.
        $this->assertSame(['actionDispatcherBefore'], $received);
    }

    public function testSucceedsWhenTheFileCannotBeDownloaded()
    {
        $this->module->shouldReceive('registerHook')->with('actionDispatcherBefore')->andReturn(true);
        $this->action->shouldReceive('ensureStoredAction')->once()->andReturn(false);

        $this->assertTrue(upgrade_module_5_3_0($this->module));
    }

    public function testFailsWhenTheHookCannotBeRegistered()
    {
        $this->module->shouldReceive('registerHook')->with('actionDispatcherBefore')->andReturn(false);
        $this->action->shouldReceive('ensureStoredAction')->once()->andReturn(true);

        $this->assertFalse(upgrade_module_5_3_0($this->module));
    }

    public function testSucceedsWhenTheActionServiceIsMissing()
    {
        $module = \Mockery::mock('Payplug');
        $module->payplug_dependencies = $this->module->payplug_dependencies;
        $module->shouldReceive('registerHook')->with('actionDispatcherBefore')->andReturn(true);
        $module->shouldReceive('getService')->with('payplug.action.apple_pay_domain_association')->andReturn(null);

        $this->assertTrue(upgrade_module_5_3_0($module));
    }

    public function testSucceedsAndLogsWhenTheActionServiceCannotBeBuilt()
    {
        $module = \Mockery::mock('Payplug');
        $module->payplug_dependencies = $this->module->payplug_dependencies;
        $module->shouldReceive('registerHook')->with('actionDispatcherBefore')->andReturn(true);
        $module->shouldReceive('getService')
            ->with('payplug.action.apple_pay_domain_association')
            ->andThrow(new \RuntimeException('container broken'));

        $this->assertTrue(upgrade_module_5_3_0($module));
        $this->assertErrorLogged('container broken');
    }

    public function testSucceedsAndLogsWhenTheSynchronisationThrows()
    {
        $this->module->shouldReceive('registerHook')->with('actionDispatcherBefore')->andReturn(true);
        $this->action->shouldReceive('ensureStoredAction')->once()->andThrow(new \Error('sync broken'));

        $this->assertTrue(upgrade_module_5_3_0($this->module));
        $this->assertErrorLogged('sync broken');
    }

    public function testKeepsTheHookRegistrationResultWhenTheSynchronisationThrows()
    {
        $this->module->shouldReceive('registerHook')->with('actionDispatcherBefore')->andReturn(false);
        $this->action->shouldReceive('ensureStoredAction')->once()->andThrow(new \RuntimeException('sync broken'));

        $this->assertFalse(upgrade_module_5_3_0($this->module));
        $this->assertErrorLogged('sync broken');
    }

    /**
     * @param string $message
     */
    private function assertErrorLogged($message)
    {
        $errors = array_values(array_filter($this->logs, function ($log) {
            return 'error' === $log['level'];
        }));
        $this->assertCount(1, $errors);
        $this->assertStringContainsString($message, $errors[0]['message']);
    }

    /**
     * @return \Mockery\MockInterface
     */
    private function getModuleObject()
    {
        return $this->module;
    }
}
