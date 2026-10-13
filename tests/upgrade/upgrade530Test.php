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

    protected $api_service;
    protected $configuration;
    protected $dependencies;
    protected $module;
    protected $saved;
    protected $stored;
    protected $throwing;

    public function setUp(): void
    {
        $this->stored = [
            'scalapay_custom_min_amounts' => '',
            'scalapay_custom_max_amounts' => '',
        ];
        $this->saved = [];
        $this->throwing = [];

        $this->configuration = \Mockery::mock('Configuration');
        $this->configuration->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                if (in_array($key, $this->throwing, true)) {
                    throw new \TypeError('Unexpected value for ' . $key);
                }

                return array_key_exists($key, $this->stored) ? $this->stored[$key] : false;
            });
        $this->configuration->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                $this->saved[$key] = $value;
                $this->stored[$key] = $value;

                return true;
            });

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog');

        $this->api_service = \Mockery::mock('ApiService');
        $this->module = \Mockery::mock('Module');
        $this->module->shouldReceive('getService')
            ->with('payplug.utilities.service.api')
            ->andReturn($this->api_service);
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module_adapter->shouldReceive('getInstanceByName')
            ->with('payplug')
            ->andReturn($this->module);

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getLogger' => $logger,
            'getModule' => $module_adapter,
        ]);

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $plugin,
            'getHelpers' => [
                'amount' => new AmountHelper($this->dependencies),
            ],
        ]);
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

    /**
     * @return \stdClass
     */
    private function getModuleObject()
    {
        $object = new \stdClass();
        $object->payplug_dependencies = $this->dependencies;

        return $object;
    }
}
