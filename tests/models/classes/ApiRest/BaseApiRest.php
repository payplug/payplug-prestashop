<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\utilities\services\Routes;
use PayPlug\src\utilities\validators\moduleValidator;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseApiRest extends TestCase
{
    use FormatDataProvider;

    public $amount_helper;
    public $classe;
    public $configuration;
    public $configuration_action;
    public $configuration_class;
    public $constant;
    public $dependencies;
    public $logger;
    public $payment_method;
    public $plugin;
    public $tools;

    protected function setUp()
    {
        $this->logger = \Mockery::mock('Logger');
        $this->logger
            ->shouldReceive([
                'addLog' => true,
            ]);

        $this->configuration = \Mockery::mock('Configuration');
        $this->configuration
            ->shouldReceive([
                'get' => true,
            ]);

        $this->constant = \Mockery::mock('Constant');
        $this->constant
            ->shouldReceive('get')
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
        $this->dependencies
            ->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            });
        $this->dependencies
            ->shouldReceive('getConfigurationKey')
            ->andReturnUsing(function ($key) {
                return $key;
            });

        $this->configuration_class = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration_class
            ->shouldReceive('getDefault')
            ->with('amounts')
            ->andReturn('{"default":{"min":"EUR:99","max":"EUR:2000000"},"oney_x3_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x3_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"bancontact":{"min":"EUR:99","max":"EUR:2000000"},"giropay":{"min":"EUR:100","max":"EUR:1000000"},"ideal":{"min":"EUR:99","max":"EUR:2000000"},"mybank":{"min":"EUR:99","max":"EUR:2000000"},"satispay":{"min":"EUR:99","max":"EUR:2000000"},"sofort":{"min":"EUR:100","max":"EUR:500000"}}');

        $this->payment_method = \Mockery::mock(PaymentMethod::class, [$this->dependencies])->makePartial();
        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation
            ->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });

        $this->tools = MockHelper::createToolsMock('PayPlug\src\application\adapter\ToolsAdapter');

        $this->tools = MockHelper::createToolsMock('PayPlug\src\application\adapter\ToolsAdapter');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $this->configuration,
                'getConfigurationAction' => $this->configuration_action,
                'getConfigurationClass' => $this->configuration_class,
                'getConstant' => $this->constant,
                'getLogger' => $this->logger,
                'getPaymentMethodClass' => $this->payment_method,
                'getRoutes' => \Mockery::mock(Routes::class)->makePartial(),
                'getTools' => $this->tools,
                'getTranslationClass' => $this->translation,
            ]);

        $this->amount_helper = \Mockery::mock(AmountHelper::class)->makePartial();
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => ['module' => \Mockery::mock(moduleValidator::class)->makePartial()],
                'getHelpers' => ['amount' => $this->amount_helper],
            ]);

        $this->classe = \Mockery::mock(ApiRest::class, [$this->dependencies])->makePartial();
        $this->dependencies->apiClass = \Mockery::mock('alias:PayPlug\classes\ApiClass');
    }
}
