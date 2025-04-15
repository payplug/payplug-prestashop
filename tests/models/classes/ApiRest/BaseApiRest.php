<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\src\utilities\helpers\ConfigurationHelper;
use PayPlug\src\utilities\services\Routes;
use PayPlug\src\utilities\validators\moduleValidator;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\AddressMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseApiRest extends TestCase
{
    use FormatDataProvider;

    protected $address_adapter;
    protected $amount_helper;
    protected $api_service;
    protected $assign_adapter;
    protected $carrier_adapter;
    protected $cart_adapter;
    protected $class;
    protected $configuration;
    protected $configuration_action;
    protected $configuration_class;
    protected $configuration_helper;
    protected $constant;
    protected $country;
    protected $currency_adapter;
    protected $dependencies;
    protected $logger;
    protected $payment_method;
    protected $plugin;
    protected $tools_adapter;
    protected $translation;
    protected $validate_adapter;

    public function setUp()
    {
        $this->api_service = \Mockery::mock('ApiService');
        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive([
            'addLog' => true,
        ]);

        $this->configuration = \Mockery::mock('Configuration');
        $this->configuration->shouldReceive([
            'get' => true,
        ]);

        $this->constant = \Mockery::mock('Constant');
        $this->constant->shouldReceive('get')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case '_PS_VERSION_':
                        return '1.7.0.0';

                    default:
                        return $key;
                }
            });

        $this->configuration_action = \Mockery::mock('ConfigurationAction');
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies->version = 'x.xx.xx';
        $this->dependencies->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            });
        $this->dependencies->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                return $key;
            });

        $this->configuration_class = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration_class->shouldReceive('getDefault')
            ->with('amounts')
            ->andReturn('{"default":{"min":"EUR:99","max":"EUR:2000000"},"oney_x3_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x3_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"bancontact":{"min":"EUR:99","max":"EUR:2000000"},"ideal":{"min":"EUR:99","max":"EUR:2000000"},"mybank":{"min":"EUR:99","max":"EUR:2000000"},"satispay":{"min":"EUR:99","max":"EUR:2000000"}}');

        $this->payment_method = \Mockery::mock(PaymentMethod::class, [$this->dependencies])->makePartial();
        $this->tools_adapter = MockHelper::createToolsMock('PayPlug\src\application\adapter\ToolsAdapter');
        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });
        $this->validate_adapter = \Mockery::mock('ValidateAdapter');
        $this->currency_adapter = \Mockery::mock('CurrencyAdapter');
        $this->assign_adapter = \Mockery::mock('AssignAdapter');
        $this->address_adapter = \Mockery::mock('AddressAdapter');
        $this->address_adapter->shouldReceive([
            'get' => AddressMock::get(),
        ]);
        $this->assign_adapter->shouldReceive([
            'assign' => '',
        ]);
        $this->carrier_adapter = \Mockery::mock('CarrierAdapter');
        $this->cart_adapter = \Mockery::mock('CartAdapter');
        $this->country = \Mockery::mock('Country');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getAddress' => $this->address_adapter,
            'getApiService' => $this->api_service,
            'getAssign' => $this->assign_adapter,
            'getCarrier' => $this->carrier_adapter,
            'getCart' => $this->cart_adapter,
            'getConfiguration' => $this->configuration,
            'getConfigurationAction' => $this->configuration_action,
            'getConfigurationClass' => $this->configuration_class,
            'getConstant' => $this->constant,
            'getCountry' => $this->country,
            'getCurrency' => $this->currency_adapter,
            'getLogger' => $this->logger,
            'getPaymentMethodClass' => $this->payment_method,
            'getRoutes' => \Mockery::mock(Routes::class)->makePartial(),
            'getTools' => $this->tools_adapter,
            'getTranslationClass' => $this->translation,
            'getValidate' => $this->validate_adapter,
        ]);

        $this->amount_helper = \Mockery::mock(AmountHelper::class)->makePartial();
        $this->configuration_helper = \Mockery::mock(ConfigurationHelper::class)->makePartial();
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
            'getValidators' => ['module' => \Mockery::mock(moduleValidator::class)->makePartial()],
            'getHelpers' => [
                'amount' => $this->amount_helper,
                'configuration' => $this->configuration_helper,
            ],
        ]);

        $this->class = \Mockery::mock(ApiRest::class, [$this->dependencies])->makePartial();
    }
}
