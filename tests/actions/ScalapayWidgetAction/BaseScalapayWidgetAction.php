<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

use PayPlug\src\actions\ScalapayWidgetAction;
use PHPUnit\Framework\TestCase;

abstract class BaseScalapayWidgetAction extends TestCase
{
    protected $action;
    protected $amount_helper;
    protected $apm_eligibility;
    protected $assigned;
    protected $config_class;
    protected $configuration;
    protected $context;
    protected $controller;
    protected $dependencies;
    protected $logs;
    protected $request;
    protected $stored;

    public function setUp(): void
    {
        parent::setUp();
        $this->resetRenderedFlags();

        $this->assigned = [];
        $this->logs = [];
        $this->controller = 'product';
        $this->request = [];
        $this->stored = [
            'payment_methods' => '{"scalapay":true}',
            'sandbox_mode' => '0',
            'scalapay_merchant_token' => 'tok_test_123',
            'scalapay_product_widget' => '1',
            'scalapay_cart_widget' => '1',
            'scalapay_checkout_widget' => '1',
        ];

        $this->configuration = \Mockery::mock('Configuration');
        $this->configuration->shouldReceive('getValue')->andReturnUsing(function ($key) {
            return array_key_exists($key, $this->stored) ? $this->stored[$key] : false;
        });

        $this->config_class = \Mockery::mock('ConfigClass');
        $this->config_class->shouldReceive('isValidFeature')->with('feature_scalapay')->andReturn(true)->byDefault();
        $this->config_class->shouldReceive('isAllowed')->andReturn(true)->byDefault();
        $this->config_class->shouldReceive('fetchTemplate')->andReturnUsing(function ($template) {
            return '<' . $template . '>';
        })->byDefault();

        $tools = \Mockery::mock('Tools');
        $tools->shouldReceive('tool')->with('getValue', \Mockery::any())->andReturnUsing(function ($action, $key) {
            return array_key_exists($key, $this->request) ? $this->request[$key] : false;
        });

        $this->context = new \stdClass();
        $this->context->language = new \stdClass();
        $this->context->language->iso_code = 'fr';
        $this->context->currency = new \stdClass();
        $this->context->currency->iso_code = 'EUR';
        $this->context->shop = new \stdClass();
        $this->context->shop->theme_name = 'classic';
        $this->context->cart = \Mockery::mock('Cart');
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn(150.0)->byDefault();
        $context_adapter = \Mockery::mock('ContextAdapter');
        $context_adapter->shouldReceive('get')->andReturn($this->context);

        $dispatcher_instance = \Mockery::mock('Dispatcher');
        $dispatcher_instance->shouldReceive('getController')->andReturnUsing(function () {
            return $this->controller;
        });
        $dispatcher = \Mockery::mock('DispatcherAdapter');
        $dispatcher->shouldReceive('getInstance')->andReturn($dispatcher_instance);

        $assign = \Mockery::mock('AssignAdapter');
        $assign->shouldReceive('assign')->andReturnUsing(function ($parameters) {
            $this->assigned = array_merge($this->assigned, $parameters);

            return true;
        });

        $this->apm_eligibility = \Mockery::mock('ApmEligibility');
        $this->apm_eligibility->shouldReceive('getApiLimits')->with('scalapay', 'EUR')->andReturn([
            'configured' => true,
            'currency_supported' => true,
            'min' => 500,
            'max' => 400000,
            'countries' => null,
        ])->byDefault();
        $this->apm_eligibility->shouldReceive('getEffectiveLimits')->with('scalapay', 'EUR')->andReturn([
            'min' => 500,
            'max' => 400000,
            'countries' => null,
            'valid' => true,
        ])->byDefault();
        $payment_method_class = \Mockery::mock('PaymentMethodClass');
        $payment_method_class->shouldReceive('getApmEligibility')->andReturn($this->apm_eligibility);

        $this->amount_helper = \Mockery::mock('AmountHelper');
        $this->amount_helper->shouldReceive('convertAmount')->andReturnUsing(function ($amount, $from_cents, $currency) {
            return (int) round($amount * 100);
        })->byDefault();
        $module = \Mockery::mock('Module');
        $module->shouldReceive('getService')->with('payplug.utilities.helper.amount')->andReturn($this->amount_helper);
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')->andReturnUsing(function ($message, $level = 'info') {
            $this->logs[] = ['level' => $level, 'message' => $message];

            return true;
        });

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive([
            'getAssign' => $assign,
            'getConfigurationClass' => $this->configuration,
            'getContext' => $context_adapter,
            'getDispatcher' => $dispatcher,
            'getLogger' => $logger,
            'getModule' => $module_adapter,
            'getPaymentMethodClass' => $payment_method_class,
            'getTools' => $tools,
        ]);

        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->dependencies->configClass = $this->config_class;
        $this->dependencies->shouldReceive('getPlugin')->andReturn($plugin);

        $this->action = \Mockery::mock(ScalapayWidgetAction::class)->makePartial();
        $this->action->dependencies = $this->dependencies;
    }

    public function tearDown(): void
    {
        $this->resetRenderedFlags();
        \Mockery::close();
        parent::tearDown();
    }

    private function resetRenderedFlags()
    {
        $reflection = new \ReflectionClass(ScalapayWidgetAction::class);
        foreach (['product_widget_rendered', 'script_rendered'] as $name) {
            $property = $reflection->getProperty($name);
            $property->setAccessible(true);
            $property->setValue(null, false);
        }
    }
}
