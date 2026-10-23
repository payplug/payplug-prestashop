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
    protected $logs;
    protected $order_state_repository;
    protected $plugin;
    protected $saved;
    protected $saved_scoped;
    protected $scoped_values;
    protected $set_result = true;
    protected $shop_adapter;
    protected $shop_groups;

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
            'payment_methods' => json_encode([
                'standard' => true,
                'satispay' => true,
                'bancontact' => true,
                'ideal' => true,
                'mybank' => true,
                'wero' => true,
                'bizum' => true,
                'scalapay' => true,
            ]),
            'scalapay_custom_min_amounts' => '',
            'scalapay_custom_max_amounts' => '',
        ];
        $this->saved = [];
        $this->saved_scoped = [];
        $this->scoped_values = [];

        $this->configuration = \Mockery::mock('ConfigurationClass');
        $this->configuration->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                return isset($this->config_values[$key]) ? $this->config_values[$key] : false;
            });
        $this->configuration->shouldReceive('getScopedValue')
            ->andReturnUsing(function ($key, $id_shop_group, $id_shop) {
                $scopes = isset($this->scoped_values[$key]) ? $this->scoped_values[$key] : [];
                if ($id_shop && null !== $scope = $this->findShopScope($key, $id_shop)) {
                    return $scopes[$scope];
                }
                if ($id_shop_group && isset($scopes[$id_shop_group . '/0'])) {
                    return $scopes[$id_shop_group . '/0'];
                }

                return isset($this->config_values[$key]) ? $this->config_values[$key] : false;
            });
        $this->configuration->shouldReceive('set')
            ->andReturnUsing(function ($key, $value, $id_shop_group = null, $id_shop = null) {
                if ($id_shop_group || $id_shop) {
                    if ($id_shop && null !== $scope = $this->findShopScope($key, $id_shop)) {
                        unset($this->scoped_values[$key][$scope]);
                    }
                    $this->saved_scoped[$id_shop_group . '/' . $id_shop][$key] = $value;
                    $this->scoped_values[$key][$id_shop_group . '/' . $id_shop] = $value;
                } else {
                    $this->saved[$key] = $value;
                    $this->config_values[$key] = $value;
                }

                return $this->set_result;
            });
        $this->configuration->shouldReceive('getName')
            ->andReturnUsing(function ($key) {
                return 'PAYPLUG_' . strtoupper($key);
            });

        $this->shop_groups = [2 => 1];
        $this->shop_adapter = \Mockery::mock('ShopAdapter');
        $this->shop_adapter->shouldReceive('get')
            ->andReturnUsing(function ($id_shop) {
                $shop = new \stdClass();
                $shop->id_shop_group = isset($this->shop_groups[$id_shop]) ? $this->shop_groups[$id_shop] : null;

                return $shop;
            });

        $this->order_state_repository = \Mockery::mock('OrderStateRepository');
        $this->order_state_repository->shouldReceive('getConfigurationsByName')
            ->andReturnUsing(function ($name) {
                return $this->getStoredRows($name);
            });

        $this->logs = [];
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive('addLog')
            ->andReturnUsing(function ($message, $level = 'info') {
                $this->logs[] = [$message, $level];

                return true;
            });

        $this->country_adapter = \Mockery::mock('CountryAdapter');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
            'getCountry' => $this->country_adapter,
            'getLogger' => $this->logger,
            'getOrderStateRepository' => $this->order_state_repository,
            'getShop' => $this->shop_adapter,
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

    /**
     * @param string $key
     * @param string $value
     * @param int $id_shop_group
     * @param int $id_shop
     */
    protected function setScopedValue($key, $value, $id_shop_group, $id_shop = 0)
    {
        $this->scoped_values[$key][$id_shop_group . '/' . $id_shop] = $value;
    }

    /**
     * @param string $key
     * @param int $id_shop
     *
     * @return string|null the stored scope of the shop, whatever shop group it was stored with
     */
    private function findShopScope($key, $id_shop)
    {
        $scopes = isset($this->scoped_values[$key]) ? $this->scoped_values[$key] : [];
        foreach (array_keys($scopes) as $scope) {
            if ((int) explode('/', $scope)[1] === (int) $id_shop) {
                return $scope;
            }
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return array rows shaped like the configuration table, NULL for the global scope
     */
    private function getStoredRows($name)
    {
        $rows = [];
        foreach ($this->config_values as $key => $value) {
            if ('PAYPLUG_' . strtoupper($key) === $name && false !== $value) {
                $rows[] = ['id_shop_group' => null, 'id_shop' => null, 'value' => $value];
            }
        }
        foreach ($this->scoped_values as $key => $values) {
            if ('PAYPLUG_' . strtoupper($key) !== $name) {
                continue;
            }
            foreach ($values as $scope => $value) {
                list($id_shop_group, $id_shop) = explode('/', $scope);
                $rows[] = [
                    'id_shop_group' => '0' === $id_shop_group ? null : $id_shop_group,
                    'id_shop' => '0' === $id_shop ? null : $id_shop,
                    'value' => $value,
                ];
            }
        }

        return $rows;
    }
}
