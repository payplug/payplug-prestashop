<?php

namespace PayPlug\tests\models\classes\paymentMethod;

use PayPlug\src\models\classes\Configuration;
use PayPlug\src\models\classes\paymentMethod\PaymentMethod;
use PayPlug\src\models\classes\Translation;
use PayPlug\src\utilities\helpers\AmountHelper;
use PayPlug\src\utilities\helpers\CookiesHelper;
use PayPlug\src\utilities\helpers\PhoneHelper;
use PayPlug\src\utilities\services\Routes;
use PayPlug\src\utilities\validators\browserValidator;
use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\AddressMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BasePaymentMethod extends TestCase
{
    use FormatDataProvider;

    protected $address;
    protected $address_adapter;
    protected $card_repository;
    protected $classe;
    protected $configuration;
    protected $constant;
    protected $context;
    protected $context_adapter;
    protected $dependencies;
    protected $helpers;
    protected $logger;
    protected $parent;
    protected $payment_repository;
    protected $plugin;
    protected $route;
    protected $tools_adapter;
    protected $translation;
    protected $validate_adapter;
    protected $validators;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies
            ->shouldReceive('l')
            ->andReturnUsing(function ($string, $name) {
                return $string;
            })
        ;

        $this->routes = \Mockery::mock(Routes::class)->makePartial();

        $this->constant = \Mockery::mock('Constant');
        $this->constant
            ->shouldReceive([
                'get' => '',
            ]);

        $this->logger = \Mockery::mock('Logger');
        $this->logger
            ->shouldReceive([
                'addLog' => true,
            ]);

        $this->tools_adapter = \Mockery::mock('ToolsAdapter');

        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation
            ->shouldReceive('l')
            ->andReturnUsing(function ($str) {
                return $str;
            });

        $this->context_adapter = \Mockery::mock('Context');
        $this->context = ContextMock::get();
        $this->context->cart = \Mockery::mock('Cart');
        $this->context->cart->id = 1;
        $this->context->cart->id_address_delivery = 42;
        $this->context->cart->id_address_invoice = 42;
        $this->context->cart->id_currency = 42;
        $this->context->cart->id_customer = 42;
        $this->context->cart->delivery_option = '';
        $this->context->cart
            ->shouldReceive([
                'getOrderTotal' => 42.42,
            ]);

        $link = \Mockery::mock('Link');
        $link->shouldReceive([
            'getAdminLink' => 'link',
            'getModuleLink' => 'link',
        ]);
        $this->context->link = $link;
        $this->context_adapter
            ->shouldReceive([
                'get' => $this->context,
            ]);

        $this->configuration = \Mockery::mock(Configuration::class, [$this->dependencies])->makePartial();
        $this->configuration
            ->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn('{"default":{"min":"EUR:99","max":"EUR:2000000"}}');

        $this->address = \Mockery::mock('Address');
        $this->address_adapter = \Mockery::mock('AddressAdapter');
        $this->address_adapter->shouldReceive([
            'get' => AddressMock::get(),
        ]);

        $this->card_repository = \Mockery::mock('CardRepository');
        $this->payment_repository = \Mockery::mock('PaymentRepository');
        $this->validate_adapter = \Mockery::mock('ValidateAdapter');

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getAddress' => $this->address_adapter,
                'getCardRepository' => $this->card_repository,
                'getConfigurationClass' => $this->configuration,
                'getConstant' => $this->constant,
                'getContext' => $this->context_adapter,
                'getLogger' => $this->logger,
                'getPaymentRepository' => $this->payment_repository,
                'getRoutes' => $this->routes,
                'getTools' => $this->tools_adapter,
                'getTranslationClass' => $this->translation,
                'getValidate' => $this->validate_adapter,
            ]);

        $this->helpers = [
            'amount' => \Mockery::mock(AmountHelper::class, [$this->dependencies])->makePartial(),
            'cookies' => \Mockery::mock(CookiesHelper::class, [$this->dependencies])->makePartial(),
            'phone' => \Mockery::mock(PhoneHelper::class)->makePartial(),
        ];

        $this->validators = [
            'browser' => \Mockery::mock(browserValidator::class)->makePartial(),
            'payment' => \Mockery::mock(paymentValidator::class)->makePartial(),
        ];

        $this->dependencies
            ->shouldReceive([
                'getHelpers' => $this->helpers,
                'getPlugin' => $this->plugin,
                'getValidators' => $this->validators,
            ]);

        $this->classe = \Mockery::mock(PaymentMethod::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
