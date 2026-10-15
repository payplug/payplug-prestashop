<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PayPlug\src\models\classes\ApmEligibility;
use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

abstract class BaseApmEligibility extends TestCase
{
    use MockeryPHPUnitIntegration;
    use FormatDataProvider;

    protected $class;
    protected $configuration;
    protected $config_values;
    protected $country_adapter;
    protected $dependencies;
    protected $logger;
    protected $plugin;
    protected $saved;
    protected $set_result = true;

    public function setUp(): void
    {
        $this->config_values = [
            'amounts' => json_encode([
                'default' => ['min' => 'EUR:99', 'max' => 'EUR:2000000'],
                'satispay' => ['min' => 'EUR:30', 'max' => 'EUR:2000000'],
                'bancontact' => ['min' => 'EUR:30', 'max' => 'EUR:2000000'],
                'ideal' => ['min' => 'EUR:30', 'max' => 'EUR:2000000'],
                'bizum' => ['min' => 'EUR:50', 'max' => 'EUR:2000000'],
                'scalapay' => ['min' => 'EUR:500', 'max' => 'EUR:200000'],
            ]),
            'countries' => json_encode([
                'satispay' => ['IT'],
                'ideal' => ['NL'],
                'bizum' => ['ES'],
                'scalapay' => ['AT', 'BE', 'DE', 'ES', 'FI', 'FR', 'IT', 'NL', 'PT'],
            ]),
            'apm_custom_settings' => '{}',
            'scalapay_custom_min_amounts' => '',
            'scalapay_custom_max_amounts' => '',
        ];
        $this->saved = [];

        $this->configuration = \Mockery::mock('ConfigurationClass');
        $this->configuration->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                return isset($this->config_values[$key]) ? $this->config_values[$key] : false;
            });
        $this->configuration->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                $this->saved[$key] = $value;
                $this->config_values[$key] = $value;

                return $this->set_result;
            });

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->country_adapter = \Mockery::mock('CountryAdapter');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getCountry' => $this->country_adapter,
            'getLogger' => $this->logger,
        ]);

        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
            'getHelpers' => [
                'amount' => new AmountHelper($this->dependencies),
            ],
        ]);

        $this->class = new ApmEligibility($this->dependencies);
    }

    /**
     * @param array $settings
     */
    protected function setCustomSettings($settings = [])
    {
        $this->config_values['apm_custom_settings'] = json_encode($settings);
    }
}
